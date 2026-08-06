<?php

namespace App\Http\Controllers;

use App\Models\InsuranceDocument;
use App\Models\InternationalInsuranceDocument;
use App\Models\TravelInsuranceDocument;
use App\Models\ResidentInsuranceDocument;
use App\Models\MarineStructureInsuranceDocument;
use App\Models\ProfessionalLiabilityInsuranceDocument;
use App\Models\PersonalAccidentInsuranceDocument;
use App\Models\SchoolStudentInsuranceDocument;
use App\Models\CashInTransitInsuranceDocument;
use App\Models\CargoInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CanceledDocumentsController extends Controller
{
    /**
     * خريطة أنواع الوثائق إلى الـ Models
     */
    private function getDocumentModels(): array
    {
        return [
            'insurance_documents' => [
                'model' => InsuranceDocument::class,
                'label' => 'تأمين السيارات',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'international_insurance_documents' => [
                'model' => InternationalInsuranceDocument::class,
                'label' => 'التأمين الدولي',
                'number_field' => 'document_number',
                'name_field' => 'insured_name',
                'type_field' => 'item_type',
            ],
            'travel_insurance_documents' => [
                'model' => TravelInsuranceDocument::class,
                'label' => 'تأمين المسافرين',
                'number_field' => 'insurance_number',
                'name_field' => null,
                'type_field' => 'insurance_type',
            ],
            'resident_insurance_documents' => [
                'model' => ResidentInsuranceDocument::class,
                'label' => 'تأمين الوافدين',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'marine_structure_insurance_documents' => [
                'model' => MarineStructureInsuranceDocument::class,
                'label' => 'تأمين الهياكل البحرية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'professional_liability_insurance_documents' => [
                'model' => ProfessionalLiabilityInsuranceDocument::class,
                'label' => 'تأمين المسؤولية المهنية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'personal_accident_insurance_documents' => [
                'model' => PersonalAccidentInsuranceDocument::class,
                'label' => 'تأمين الحوادث الشخصية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'school_student_insurance_documents' => [
                'model' => SchoolStudentInsuranceDocument::class,
                'label' => 'تأمين طلاب المدارس',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'cash_in_transit_insurance_documents' => [
                'model' => CashInTransitInsuranceDocument::class,
                'label' => 'تأمين نقل النقدية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
            'cargo_insurance_documents' => [
                'model' => CargoInsuranceDocument::class,
                'label' => 'تأمين شحن البضائع',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'type_field' => 'insurance_type',
            ],
        ];
    }

    /**
     * التحقق من صلاحية رؤية الوثائق الملغية
     */
    private function checkUserPermission(Request $request): array
    {
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if (!$userId) {
            return ['allowed' => false, 'isAdmin' => false, 'agentId' => null];
        }

        $userId = is_numeric($userId) ? (int) $userId : null;
        $user = $userId ? User::find($userId) : null;
        if (!$user) {
            return ['allowed' => false, 'isAdmin' => false, 'agentId' => null];
        }

        $isAdmin = $user->is_admin ?? false;
        if ($isAdmin) {
            return ['allowed' => true, 'isAdmin' => true, 'agentId' => null];
        }

        // الوكلاء
        $agentId = $user->branch_agent_id;
        if (!$agentId) {
            $agent = BranchAgent::where('user_id', $user->id)->first();
            if ($agent) {
                $agentId = $agent->id;
            }
        }

        // الموظفين ذوي الصلاحيات
        $authDocs = is_array($user->authorized_documents)
            ? $user->authorized_documents
            : (is_string($user->authorized_documents) ? json_decode($user->authorized_documents, true) : []);
        if (!is_array($authDocs)) {
            $authDocs = [];
        }

        // يمتلك صلاحية إذا كان أدمن أو لديه أي صلاحيات وثائق أو قسم أو هو وكيل
        $hasDocPermission = !empty($authDocs) || $user->department_id !== null || $agentId !== null;

        if ($hasDocPermission) {
            return ['allowed' => true, 'isAdmin' => false, 'agentId' => $agentId];
        }

        return ['allowed' => false, 'isAdmin' => false, 'agentId' => null];
    }

    /**
     * جلب جميع الوثائق الملغية من كل الجداول مع فلترة متقدمة
     */
    public function index(Request $request)
    {
        try {
            $perm = $this->checkUserPermission($request);
            if (!$perm['allowed']) {
                return response()->json(['message' => 'غير مصرح لك بعرض الوثائق الملغية'], 403);
            }

            $isAdmin = $perm['isAdmin'];
            $userAgentId = $perm['agentId'];

            $models = $this->getDocumentModels();
            $allCanceled = [];

            $search = $request->query('search', '');
            $filterType = $request->query('type', '');
            $filterAgentId = $request->query('branch_agent_id', '');

            // إذا كان المستخدم وكيلاً وليس أدمن، يرى وثائق وكالته فقط
            if (!$isAdmin && $userAgentId && !$filterAgentId) {
                $filterAgentId = $userAgentId;
            }

            $filterYear = $request->query('year', '');
            $filterMonth = $request->query('month', '');
            $filterDay = $request->query('day', '');
            $perPage = (int) $request->query('per_page', 15);
            $page = (int) $request->query('page', 1);


            $this->syncLifoCancelledCardsToDb();

            foreach ($models as $tableKey => $info) {
                // تخطي إذا تم تحديد نوع معين
                if ($filterType && $filterType !== $tableKey) {
                    continue;
                }

                $modelClass = $info['model'];
                $tableName = (new $modelClass)->getTable();

                $query = $modelClass::where(function ($q) use ($tableName) {
                    $q->where('is_canceled', true);
                    if (\Illuminate\Support\Facades\Schema::hasColumn($tableName, 'status')) {
                        $q->orWhere('status', 'ملغية')->orWhere('status', 'ملغيه');
                    }
                });

                // فلتر الوكيل
                if ($filterAgentId) {
                    $query->where('branch_agent_id', $filterAgentId);
                }

                // فلاتر التاريخ (تاريخ الإلغاء)
                if ($filterYear) {
                    $query->whereYear('canceled_at', $filterYear);
                }
                if ($filterMonth) {
                    $query->whereMonth('canceled_at', $filterMonth);
                }
                if ($filterDay) {
                    $query->whereDay('canceled_at', $filterDay);
                }

                // فلتر البحث
                if ($search) {
                    $numberField = $info['number_field'];
                    $nameField = $info['name_field'];
                    $query->where(function ($q) use ($search, $numberField, $nameField) {
                        $q->where($numberField, 'like', "%{$search}%");
                        if ($nameField) {
                            $q->orWhere($nameField, 'like', "%{$search}%");
                        }
                    });
                }

                $docs = $query->with('branchAgent')->get();

                foreach ($docs as $doc) {
                    $allCanceled[] = [
                        'id' => $doc->id,
                        'table' => $tableKey,
                        'doc_type_label' => $info['label'],
                        'insurance_number' => $doc->{$info['number_field']} ?? '-',
                        'insurance_type' => $doc->{$info['type_field']} ?? $info['label'],
                        'insured_name' => ($info['name_field'] && isset($doc->{$info['name_field']})) ? $doc->{$info['name_field']} : '-',
                        'total' => (float) ($doc->total ?? 0),
                        'premium' => (float) ($doc->premium ?? 0),
                        'issue_date' => $doc->issue_date ?? null,
                        'start_date' => $doc->start_date ?? null,
                        'end_date' => $doc->end_date ?? null,
                        'branch_agent_id' => $doc->branch_agent_id ?? null,
                        'agency_name' => $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : '-',
                        'canceled_at' => $doc->canceled_at ?? null,
                        'canceled_by' => $doc->canceled_by ?? null,
                        'cancel_reason' => $doc->cancel_reason ?? '-',
                    ];
                }
            }

            // ترتيب حسب تاريخ الإلغاء الأحدث
            usort($allCanceled, function ($a, $b) {
                return strtotime($b['canceled_at'] ?? '0') - strtotime($a['canceled_at'] ?? '0');
            });

            // إجمالي قيمة الوثائق الملغية
            $totalValue = array_sum(array_column($allCanceled, 'total'));
            $totalCount = count($allCanceled);

            // تطبيق Pagination يدوي
            $offset = ($page - 1) * $perPage;
            $paginated = array_slice($allCanceled, $offset, $perPage);

            return response()->json([
                'data' => $paginated,
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $perPage > 0 ? (int) ceil($totalCount / $perPage) : 1,
                'summary' => [
                    'total_count' => $totalCount,
                    'total_value' => round($totalValue, 3),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error in CanceledDocumentsController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الوثائق الملغية',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * إحصائيات الوثائق الملغية لكل وكيل
     */
    public function stats(Request $request)
    {
        try {
            $perm = $this->checkUserPermission($request);
            if (!$perm['allowed']) {
                return response()->json(['message' => 'غير مصرح لك بعرض الوثائق الملغية'], 403);
            }

            $isAdmin = $perm['isAdmin'];
            $userAgentId = $perm['agentId'];

            $filterAgentId = $request->query('branch_agent_id', '');
            if (!$isAdmin && $userAgentId && !$filterAgentId) {
                $filterAgentId = $userAgentId;
            }


            $models = $this->getDocumentModels();
            $stats = [
                'total_canceled' => 0,
                'total_value' => 0,
                'by_type' => [],
                'by_agent' => [],
            ];

            foreach ($models as $tableKey => $info) {
                $modelClass = $info['model'];

                if (!\Illuminate\Support\Facades\Schema::hasColumn(
                    (new $modelClass)->getTable(),
                    'is_canceled'
                )) {
                    continue;
                }

                $query = $modelClass::where('is_canceled', true);

                if ($filterAgentId) {
                    $query->where('branch_agent_id', $filterAgentId);
                } elseif (!$isAdmin && $userId) {
                    // الوكيل يرى فقط وثائقه
                    $branchAgent = BranchAgent::where('user_id', $userId)->first();
                    if ($branchAgent) {
                        $query->where('branch_agent_id', $branchAgent->id);
                    } else {
                        continue;
                    }
                }

                $count = $query->count();
                $total = $query->sum('total');

                if ($count > 0) {
                    $stats['total_canceled'] += $count;
                    $stats['total_value'] += $total;
                    $stats['by_type'][$tableKey] = [
                        'label' => $info['label'],
                        'count' => $count,
                        'total_value' => round((float) $total, 3),
                    ];
                }
            }

            $stats['total_value'] = round($stats['total_value'], 3);

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Error in CanceledDocumentsController@stats: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * مزامنة وتأكيد ربط البطاقات الملغية من LIFO بالوكلاء والجدول المحلي
     */
    private function syncLifoCancelledCardsToDb()
    {
        try {
            $cards = \Illuminate\Support\Facades\Cache::get('lifo_cards_adminmli_all');
            if (!$cards) {
                $cards = \Illuminate\Support\Facades\Cache::get('lifo_cards_adminmli_cancel');
            }
            if (!is_array($cards) || empty($cards)) {
                return;
            }

            $agents = BranchAgent::all();
            $agentsMap = [];
            foreach ($agents as $ag) {
                if ($ag->agency_name) $agentsMap[mb_strtolower(trim($ag->agency_name))] = $ag->id;
                if ($ag->code) $agentsMap[mb_strtolower(trim($ag->code))] = $ag->id;
                if ($ag->agent_name) $agentsMap[mb_strtolower(trim($ag->agent_name))] = $ag->id;
            }

            foreach ($cards as $card) {
                $status = $card['cardstautesname'] ?? '';
                if ($status !== 'البطاقات الملغية' && $status !== 'الملغية') {
                    continue;
                }

                $num = $card['card_number'] ?? $card['card_serial'] ?? null;
                if (!$num) continue;

                $officeName = trim($card['offices'] ?? '');
                $agentId = null;

                if ($officeName) {
                    $lowerOffice = mb_strtolower($officeName);
                    if (isset($agentsMap[$lowerOffice])) {
                        $agentId = $agentsMap[$lowerOffice];
                    } else {
                        foreach ($agents as $ag) {
                            if (!empty($ag->code) && str_contains($lowerOffice, mb_strtolower($ag->code))) {
                                $agentId = $ag->id;
                                break;
                            }
                            if (!empty($ag->agency_name) && (str_contains($lowerOffice, mb_strtolower($ag->agency_name)) || str_contains(mb_strtolower($ag->agency_name), $lowerOffice))) {
                                $agentId = $ag->id;
                                break;
                            }
                        }
                    }
                }

                $existing = \Illuminate\Support\Facades\DB::table('international_insurance_documents')
                    ->where('document_number', $num)
                    ->first();

                if ($existing) {
                    \Illuminate\Support\Facades\DB::table('international_insurance_documents')
                        ->where('id', $existing->id)
                        ->update([
                            'is_canceled' => 1,
                            'canceled_at' => $existing->canceled_at ?? (!empty($card['created_at']) ? substr($card['created_at'], 0, 19) : now()->toDateTimeString()),
                            'cancel_reason' => $existing->cancel_reason ?? 'إلغاء البطاقة من خادم الاتحاد (LIFO)',
                            'branch_agent_id' => $existing->branch_agent_id ?? $agentId,
                        ]);
                } else {
                    \Illuminate\Support\Facades\DB::table('international_insurance_documents')->insert([
                        'document_number'        => $num,
                        'external_policy_number' => $card['id'] ?? null,
                        'insured_name'           => 'بطاقة برتقالية ملغية (LIFO)',
                        'item_type'              => 'سيارات',
                        'total'                  => 0.000,
                        'premium'                => 0.000,
                        'tax'                    => 0.000,
                        'supervision_fees'       => 0.000,
                        'issue_fees'             => 0.000,
                        'stamp'                  => 0.000,
                        'issue_date'             => !empty($card['created_at']) ? substr($card['created_at'], 0, 19) : now()->toDateTimeString(),
                        'branch_agent_id'        => $agentId,
                        'is_canceled'            => 1,
                        'canceled_at'            => !empty($card['created_at']) ? substr($card['created_at'], 0, 19) : now()->toDateTimeString(),
                        'cancel_reason'          => 'إلغاء البطاقة من خادم الاتحاد (LIFO)',
                        'created_at'             => now(),
                        'updated_at'             => now(),
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in syncLifoCancelledCardsToDb: ' . $e->getMessage());
        }
    }
}
