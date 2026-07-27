<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Models
use App\Models\InsuranceDocument;
use App\Models\InternationalInsuranceDocument;
use App\Models\TravelInsuranceDocument;
use App\Models\TravelInsurancePassenger;
use App\Models\ResidentInsuranceDocument;
use App\Models\ResidentInsurancePassenger;
use App\Models\MarineStructureInsuranceDocument;
use App\Models\ProfessionalLiabilityInsuranceDocument;
use App\Models\PersonalAccidentInsuranceDocument;
use App\Models\SchoolStudentInsuranceDocument;
use App\Models\CashInTransitInsuranceDocument;
use App\Models\CargoInsuranceDocument;
use App\Models\BranchAgent;

class OldDocumentController extends Controller
{
    /**
     * Store an old document in its respective table.
     */
    public function store(Request $request)
    {
        try {
            $documentType = $request->input('document_type');
            $branchAgentId = $request->input('branch_agent_id');
            $issueDateStr = $request->input('issue_date', now()->format('Y-m-d'));
            $issueDate = Carbon::parse($issueDateStr);

            // 1. تحديد الـ Model واسم حقل الرقم الفريد
            $mapping = [
                'compulsory' => [
                    'model' => InsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'BKMCI',
                ],
                'international' => [
                    'model' => InternationalInsuranceDocument::class,
                    'number_field' => 'document_number',
                    'default_prefix' => 'LBY',
                ],
                'travel' => [
                    'model' => TravelInsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'BKTRV',
                ],
                'resident' => [
                    'model' => ResidentInsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'MLEPT',
                ],
                'marine' => [
                    'model' => MarineStructureInsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'BKMAR',
                ],
                'medical' => [
                    'model' => ProfessionalLiabilityInsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'BKMED',
                ],
                'personal_accident' => [
                    'model' => PersonalAccidentInsuranceDocument::class,
                    'number_field' => 'insurance_number',
                    'default_prefix' => 'BKPAC',
                ],
                'school_student' => [
                    'model' => SchoolStudentInsuranceDocument::class,
                    'number_field' => 'policy_number',
                    'default_prefix' => 'BKSCH',
                ],
                'cash_in_transit' => [
                    'model' => CashInTransitInsuranceDocument::class,
                    'number_field' => 'policy_number',
                    'default_prefix' => 'BKCSH',
                ],
                'cargo' => [
                    'model' => CargoInsuranceDocument::class,
                    'number_field' => 'policy_number',
                    'default_prefix' => 'BKCRG',
                ],
            ];

            if (!isset($mapping[$documentType])) {
                return response()->json([
                    'success' => false,
                    'message' => 'نوع الوثيقة غير صالح'
                ], 400);
            }

            $config = $mapping[$documentType];
            $modelClass = $config['model'];
            $numberField = $config['number_field'];
            $prefix = $config['default_prefix'];

            // 2. تحديد رقم الوثيقة المدخل يدويًا أو توليده تلقائيًا
            $documentNumber = $request->input('document_number') 
                ?? $request->input($numberField) 
                ?? $request->input('insurance_number') 
                ?? $request->input('policy_number');

            if (empty($documentNumber)) {
                // توليد رقم تلقائي
                $lastDoc = $modelClass::where($numberField, 'like', "{$prefix}%")
                    ->orderBy('id', 'desc')
                    ->first();
                if ($lastDoc && preg_match("/{$prefix}(\d+)/", $lastDoc->$numberField, $matches)) {
                    $nextNumber = (int)$matches[1] + 1;
                } else {
                    $nextNumber = 1;
                }
                do {
                    $documentNumber = $prefix . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                    $nextNumber++;
                } while ($modelClass::where($numberField, $documentNumber)->exists());
            }

            // 3. إنشاء أو استرجاع كائن الموديل وتعبئة الحقول
            $document = $modelClass::firstOrNew([$numberField => $documentNumber]);
            $isUpdate = $document->exists;
            $document->timestamps = false; // إلغاء التحديث التلقائي للتواريخ

            $columns = Schema::getColumnListing($document->getTable());

            // تعيين الحقول العامة والخاصة بالوثائق القديمة
            $document->$numberField = $documentNumber;
            if (in_array('is_old_document', $columns)) {
                $document->is_old_document = true;
            }
            
            // في بعض الجداول اسم حقل التاريخ هو issue_date كـ timestamp
            if (in_array('issue_date', $columns)) {
                $document->issue_date = $issueDate;
            }
            if (in_array('created_at', $columns)) {
                $document->created_at = $issueDate;
            }
            if (in_array('updated_at', $columns)) {
                $document->updated_at = $issueDate;
            }

            // تعيين الوكيل
            if (in_array('branch_agent_id', $columns)) {
                $document->branch_agent_id = $branchAgentId;
            }

            // تعبئة باقي الحقول الممررة في الطلب والتي تتطابق مع أعمدة الجدول
            foreach ($request->all() as $key => $value) {
                if ($key === 'work_place' && in_array('workplace', $columns)) {
                    $document->workplace = $value;
                    continue;
                }
                if ($key === 'job' && in_array('profession', $columns)) {
                    $document->profession = $value;
                    continue;
                }
                if ($key === 'insured_name' && in_array('name', $columns)) {
                    $document->name = $value;
                    continue;
                }
                if ($key === 'nid_passport' && in_array('id_proof', $columns)) {
                    $document->id_proof = $value;
                    continue;
                }
                if (in_array($key, $columns) && !in_array($key, ['id', 'created_at', 'updated_at', 'issue_date', $numberField, 'branch_agent_id'])) {
                    if ($key === 'gender') {
                        if ($value === 'ذكر' || $value === 'ذكر Male') {
                            $value = 'ذكر Male';
                        } elseif ($value === 'أنثى' || $value === 'انثى' || $value === 'انثى Female' || $value === 'أنثى Female') {
                            $value = 'انثى Female';
                        }
                    }
                    $document->$key = $value;
                }
            }

            // بالنسبة للتأمين الإجباري، نحدد حالة المزامنة مع الهيئة بـ null أو نجاح لتجنب أي محاولة مزامنة لاحقة
            if ($documentType === 'compulsory' && in_array('eidc_sync_status', $columns)) {
                $document->eidc_sync_status = 'synced'; // معلمة كمزامن لتفادي محاولات المزامنة
            }

            // حفظ الوثيقة
            $document->save();

            // 4. معالجة وحفظ بيانات المسافرين لتأمين السفر والوافدين
            if ($documentType === 'travel') {
                TravelInsurancePassenger::updateOrCreate(
                    [
                        'travel_insurance_document_id' => $document->id,
                        'is_main_passenger' => true,
                    ],
                    [
                        'name_ar' => $request->input('insured_name') ?? $request->input('name_ar') ?? '-',
                        'name_en' => $request->input('name_en') ?? '-',
                        'phone' => $request->input('phone'),
                        'whatsapp_number' => $request->input('whatsapp_number'),
                        'passport_number' => $request->input('passport_number') ?? $request->input('nid_passport'),
                        'address' => $request->input('address'),
                        'birth_date' => $request->input('birth_date'),
                        'age' => $request->input('age'),
                        'gender' => $request->input('gender', 'ذكر'),
                        'nationality' => $request->input('nationality', 'ليبي'),
                    ]
                );
            } elseif ($documentType === 'resident') {
                ResidentInsurancePassenger::updateOrCreate(
                    [
                        'resident_insurance_document_id' => $document->id,
                        'is_main_passenger' => true,
                    ],
                    [
                        'name_ar' => $request->input('insured_name') ?? $request->input('name_ar') ?? '-',
                        'name_en' => $request->input('name_en') ?? '-',
                        'phone' => $request->input('phone'),
                        'whatsapp_number' => $request->input('whatsapp_number'),
                        'passport_number' => $request->input('passport_number') ?? $request->input('nid_passport'),
                        'address' => $request->input('address'),
                        'birth_date' => $request->input('birth_date'),
                        'age' => $request->input('age'),
                        'gender' => $request->input('gender', 'ذكر'),
                        'nationality' => $request->input('nationality', 'ليبي'),
                        'occupation' => $request->input('occupation') ?? $request->input('profession'),
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => $isUpdate ? 'تم تحديث الوثيقة القديمة بنجاح بالتاريخ المحدد' : 'تم حفظ الوثيقة القديمة بنجاح بالتاريخ المحدد',
                'document' => $document
            ], $isUpdate ? 200 : 201);

        } catch (\Exception $e) {
            Log::error('Error storing old document: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ الوثيقة القديمة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Get a list of old documents across document tables.
     */
    public function index(Request $request)
    {
        try {
            $branchAgentId = $request->get('branch_agent_id');
            $documentType  = $request->get('document_type');
            $search        = $request->get('search');
            $perPage       = (int)$request->get('per_page', 100);

            $documentTables = [
                'compulsory' => [
                    'table' => 'insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين إجباري سيارات',
                ],
                'international' => [
                    'table' => 'international_insurance_documents',
                    'number_field' => 'document_number',
                    'type_label' => 'تأمين السيارات الدولي',
                ],
                'travel' => [
                    'table' => 'travel_insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين المسافرين',
                ],
                'resident' => [
                    'table' => 'resident_insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين الوافدين للمقيمين',
                ],
                'marine' => [
                    'table' => 'marine_structure_insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين الهياكل البحرية',
                ],
                'medical' => [
                    'table' => 'professional_liability_insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين المسؤولية المهنية (الطبية)',
                ],
                'personal_accident' => [
                    'table' => 'personal_accident_insurance_documents',
                    'number_field' => 'insurance_number',
                    'type_label' => 'تأمين الحوادث الشخصية',
                ],
                'school_student' => [
                    'table' => 'school_student_insurance_documents',
                    'number_field' => 'policy_number',
                    'type_label' => 'تأمين حماية طلاب المدارس',
                ],
                'cash_in_transit' => [
                    'table' => 'cash_in_transit_insurance_documents',
                    'number_field' => 'policy_number',
                    'type_label' => 'تأمين نقل النقدية',
                ],
                'cargo' => [
                    'table' => 'cargo_insurance_documents',
                    'number_field' => 'policy_number',
                    'type_label' => 'تأمين شحن البضائع',
                ],
            ];

            $agentsMap = BranchAgent::pluck('agency_name', 'id')->toArray();

            $results = [];

            foreach ($documentTables as $typeKey => $config) {
                if ($documentType && $documentType !== 'all' && $documentType !== $typeKey) {
                    continue;
                }

                $tableName   = $config['table'];
                $numberField = $config['number_field'];
                $typeLabel   = $config['type_label'];

                if (!Schema::hasTable($tableName)) {
                    continue;
                }

                $query = DB::table($tableName);

                if (Schema::hasColumn($tableName, 'is_old_document')) {
                    $query->where('is_old_document', true);
                }

                if ($branchAgentId) {
                    if (Schema::hasColumn($tableName, 'branch_agent_id')) {
                        $query->where('branch_agent_id', $branchAgentId);
                    }
                }

                if ($search) {
                    $query->where(function ($q) use ($numberField, $search, $tableName) {
                        $q->where($numberField, 'like', "%{$search}%");
                        if (Schema::hasColumn($tableName, 'insured_name')) {
                            $q->orWhere('insured_name', 'like', "%{$search}%");
                        }
                        if (Schema::hasColumn($tableName, 'name')) {
                            $q->orWhere('name', 'like', "%{$search}%");
                        }
                        if (Schema::hasColumn($tableName, 'student_name')) {
                            $q->orWhere('student_name', 'like', "%{$search}%");
                        }
                    });
                }

                $docs = $query->orderBy('id', 'desc')->limit(100)->get();

                foreach ($docs as $doc) {
                    $docNum = $doc->$numberField ?? ($doc->insurance_number ?? $doc->document_number ?? $doc->policy_number ?? '-');
                    $name   = $doc->insured_name ?? $doc->name ?? $doc->student_name ?? '-';
                    $agentId = $doc->branch_agent_id ?? null;
                    $agentName = $agentId && isset($agentsMap[$agentId]) ? $agentsMap[$agentId] : '-';
                    $startDate = $doc->start_date ?? $doc->issue_date ?? $doc->created_at ?? '-';
                    $endDate   = $doc->end_date ?? '-';
                    $total     = $doc->total ?? $doc->premium ?? $doc->premium_amount ?? 0;
                    $createdAt = $doc->created_at ?? $doc->issue_date ?? '-';

                    $results[] = [
                        'id'              => $doc->id,
                        'document_type'   => $typeKey,
                        'type_label'      => $doc->insurance_type ?? $typeLabel,
                        'document_number' => $docNum,
                        'insured_name'    => $name,
                        'branch_agent_id' => $agentId,
                        'agent_name'      => $agentName,
                        'start_date'      => $startDate ? substr((string)$startDate, 0, 10) : '-',
                        'end_date'        => $endDate ? substr((string)$endDate, 0, 10) : '-',
                        'total'           => (float)$total,
                        'created_at'      => $createdAt ? substr((string)$createdAt, 0, 10) : '-',
                    ];
                }
            }

            $page     = (int)$request->get('page', 1);
            $perPage  = (int)$request->get('per_page', 15);
            $offset   = max(0, ($page - 1) * $perPage);

            // Sort merged results by created_at / id desc
            usort($results, function ($a, $b) {
                return strcmp((string)$b['created_at'], (string)$a['created_at']);
            });

            $totalCount = count($results);
            $lastPage   = max(1, (int)ceil($totalCount / $perPage));
            $slicedResults = array_slice($results, $offset, $perPage);

            return response()->json([
                'success'      => true,
                'data'         => $slicedResults,
                'total'        => $totalCount,
                'current_page' => $page,
                'per_page'     => $perPage,
                'last_page'    => $lastPage,
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching old documents list: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الوثائق القديمة',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
