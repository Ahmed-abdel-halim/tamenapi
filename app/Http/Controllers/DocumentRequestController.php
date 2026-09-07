<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentRequestController extends Controller
{
    private function getDocumentModels(): array
    {
        return [
            'تأمين سيارات' => \App\Models\InsuranceDocument::class,
            'تأمين إجباري سيارات' => \App\Models\InsuranceDocument::class,
            'تأمين سيارة جمرك' => \App\Models\InsuranceDocument::class,
            'تأمين طرف ثالث سيارات' => \App\Models\InsuranceDocument::class,
            'تأمين سيارات أجنبية' => \App\Models\InsuranceDocument::class,
            'تأمين سيارات دولي' => \App\Models\InternationalInsuranceDocument::class,
            'تأمين المسافرين' => \App\Models\TravelInsuranceDocument::class,
            'تأمين طبي (مسافرين)' => \App\Models\TravelInsuranceDocument::class,
            'تأمين زائرين ليبيا' => \App\Models\TravelInsuranceDocument::class,
            'تأمين الوافدين' => \App\Models\ResidentInsuranceDocument::class,
            'تأمين طبي (وافدين)' => \App\Models\ResidentInsuranceDocument::class,
            'تأمين الهياكل البحرية' => \App\Models\MarineStructureInsuranceDocument::class,
            'تأمين هياكل بحرية' => \App\Models\MarineStructureInsuranceDocument::class,
            'تأمين المسؤولية المهنية (الطبية)' => \App\Models\ProfessionalLiabilityInsuranceDocument::class,
            'تأمين مسؤولية مهنية' => \App\Models\ProfessionalLiabilityInsuranceDocument::class,
            'تأمين الحوادث الشخصية' => \App\Models\PersonalAccidentInsuranceDocument::class,
            'تأمين حوادث شخصية' => \App\Models\PersonalAccidentInsuranceDocument::class,
            'تأمين حماية طلاب المدارس' => \App\Models\SchoolStudentInsuranceDocument::class,
            'تأمين حماية طلاب مدارس' => \App\Models\SchoolStudentInsuranceDocument::class,
            'تأمين نقل النقدية' => \App\Models\CashInTransitInsuranceDocument::class,
            'تأمين شحن البضائع' => \App\Models\CargoInsuranceDocument::class,
            'تأمين نقل بضائع' => \App\Models\CargoInsuranceDocument::class,
        ];
    }

    public function index(Request $request)
    {
        $query = DocumentRequest::with(['branchAgent', 'user', 'reviewer']);

        // If not admin, only show requests for their branch agent
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && !($user->is_admin ?? false)) {
                $agent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($agent) {
                    $query->where('branch_agent_id', $agent->id);
                } else {
                    $query->where('user_id', $userId);
                }
            }
        }

        // Filter by branch_agent_id for admins
        if ($request->has('branch_agent_id')) {
            $query->where('branch_agent_id', $request->query('branch_agent_id'));
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|string|in:modification,cancellation',
            'document_type' => 'nullable|string',
            'document_id' => 'nullable|integer',
            'document_number' => 'required|string',
            'insured_name' => 'nullable|string',
            'subject' => 'nullable|string',
            'cancellation_reason' => 'nullable|string',
            'cancellation_reason_other' => 'nullable|string',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'legal_acknowledged' => 'nullable|boolean',
        ]);

        $userId = $request->header('X-User-Id') ?? $request->input('user_id');
        $agentId = null;
        $applicantName = null;

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $applicantName = $user->name ?? $user->username;
                $agent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($agent) {
                    $agentId = $agent->id;
                } elseif ($user->branch_agent_id) {
                    $agentId = $user->branch_agent_id;
                }
            }
        }

        // منع تقديم أكثر من طلب إلغاء لنفس الوثيقة إذا كان هناك طلب معلق أو الوثيقة ملغية بالفعل
        if ($validated['request_type'] === 'cancellation') {
            // 1. التحقق من وجود طلب إلغاء قيد المراجعة حالياً لنفس الوثيقة
            $pendingRequest = DocumentRequest::where('request_type', 'cancellation')
                ->where('document_number', $validated['document_number'])
                ->where('status', 'pending')
                ->first();

            if ($pendingRequest) {
                return response()->json([
                    'message' => "يوجد بالفعل طلب إلغاء قيد المراجعة لهذه الوثيقة برقم كود ({$pendingRequest->request_code}). يرجى انتظار قرار الإدارة ولا يمكن تقديم طلب مكرر."
                ], 422);
            }

            // 2. التحقق هل تم قبول إلغاء هذه الوثيقة مسبقاً
            $acceptedRequest = DocumentRequest::where('request_type', 'cancellation')
                ->where('document_number', $validated['document_number'])
                ->where('status', 'accepted')
                ->first();

            if ($acceptedRequest) {
                return response()->json([
                    'message' => "تم اعتماد إلغاء هذه الوثيقة مسبقاً بموجب الطلب ({$acceptedRequest->request_code}) وهي ملغية بالفعل في المنظومة."
                ], 422);
            }

            // 3. التحقق المباشر من جدول الوثيقة
            $modelsMap = $this->getDocumentModels();
            $checkModels = [];
            if (!empty($validated['document_type']) && isset($modelsMap[$validated['document_type']])) {
                $checkModels[] = $modelsMap[$validated['document_type']];
            } else {
                $checkModels = array_unique(array_values($modelsMap));
            }

            foreach ($checkModels as $modelClass) {
                $field = ($modelClass === \App\Models\InternationalInsuranceDocument::class) ? 'document_number' : 'insurance_number';
                $foundDoc = $modelClass::where($field, $validated['document_number'])->first();
                if ($foundDoc && ($foundDoc->is_canceled ?? false)) {
                    return response()->json([
                        'message' => "هذه الوثيقة ملغية بالفعل في سجلات النظام ولا يمكن تقديم طلب إلغاء جديد لها."
                    ], 422);
                }
            }
        }

        // Generate unique request code: AL-000001
        $lastRecord = DocumentRequest::where('request_code', 'like', 'AL-%')
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = 1;
        if ($lastRecord && preg_match('/AL-(\d+)/', $lastRecord->request_code, $matches)) {
            $nextNum = ((int)$matches[1]) + 1;
        }
        $requestCode = 'AL-' . str_pad($nextNum, 6, '0', STR_PAD_LEFT);

        $subject = $validated['subject'] ?? ($validated['request_type'] === 'cancellation' ? 'طلب إلغاء وثيقة' : 'طلب تعديل وثيقة');
        $description = $validated['description'] ?? ($validated['cancellation_reason'] ?? $subject);

        $documentRequest = DocumentRequest::create([
            'request_code' => $requestCode,
            'branch_agent_id' => $agentId,
            'user_id' => $userId,
            'applicant_name' => $applicantName,
            'request_type' => $validated['request_type'],
            'document_type' => $validated['document_type'] ?? null,
            'document_id' => $validated['document_id'] ?? null,
            'document_number' => $validated['document_number'],
            'insured_name' => $validated['insured_name'] ?? null,
            'subject' => $subject,
            'cancellation_reason' => $validated['cancellation_reason'] ?? null,
            'cancellation_reason_other' => $validated['cancellation_reason_other'] ?? null,
            'description' => $description,
            'notes' => $validated['notes'] ?? null,
            'legal_acknowledged' => (bool)($validated['legal_acknowledged'] ?? false),
            'status' => 'pending'
        ]);

        // إرسال إشعار للمشرفين
        try {
            $admins = \App\Models\User::where('is_admin', true)->get();
            $agentName = $documentRequest->branchAgent?->agency_name ?? ($applicantName ?: 'الوكيل');
            $reqTypeArabic = $documentRequest->request_type === 'cancellation' ? 'إلغاء وثيقة' : 'تعديل وثيقة';
            $title = "طلب {$reqTypeArabic} جديد ({$requestCode})";
            $message = "طلب جديد ({$reqTypeArabic}) للوثيقة رقم ({$documentRequest->document_number}) من: {$agentName}";
            $url = "/document-requests";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'info', $url));
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in DocumentRequest store: ' . $ne->getMessage());
        }

        return response()->json($documentRequest->load(['branchAgent', 'user']), 201);
    }

    public function update(Request $request, $id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,rejected',
            'admin_message' => 'nullable|string'
        ]);

        $reviewerId = $request->header('X-User-Id') ?? $request->input('user_id');

        $documentRequest->update([
            'status' => $validated['status'],
            'admin_message' => $validated['admin_message'] ?? null,
            'reviewed_by' => $reviewerId,
            'reviewed_at' => now(),
        ]);

        // إذا تم قبول طلب الإلغاء، نقوم بإلغاء الوثيقة فعلياً في جدولها بقاعدة البيانات
        if ($validated['status'] === 'accepted' && $documentRequest->request_type === 'cancellation') {
            $this->cancelTargetDocument($documentRequest, $reviewerId);
        }

        // إرسال إشعار للوكيل ولطالب الإلغاء
        try {
            $title = '';
            $message = '';
            $type = 'info';

            if ($validated['status'] === 'accepted') {
                $title = 'تمت الموافقة على طلب إلغاء الوثيقة';
                $message = "تمت الموافقة على طلب إلغاء الوثيقة رقم ({$documentRequest->document_number}) كود ({$documentRequest->request_code}) بنجاح، وأصبحت الوثيقة ملغية في النظام.";
                $type = 'success';
            } else {
                $reasonNote = !empty($validated['admin_message']) ? " السبب: " . $validated['admin_message'] : "";
                $title = 'تم رفض طلب إلغاء الوثيقة';
                $message = "تم رفض طلب إلغاء الوثيقة رقم ({$documentRequest->document_number}) كود ({$documentRequest->request_code}).{$reasonNote} وتظل الوثيقة نشطة وسارية.";
                $type = 'error';
            }

            $url = "/document-requests";

            if ($documentRequest->user_id) {
                $agentUser = \App\Models\User::find($documentRequest->user_id);
                if ($agentUser) {
                    $url = $agentUser->branchAgent ? "/branches-agents/{$agentUser->branchAgent->id}?tab=doc_requests" : "/document-requests";
                    $agentUser->notify(new \App\Notifications\SystemNotification($title, $message, $type, $url));
                }
            }

            // إشعار صاحب الوكالة الأساسي إذا كان مختلفاً عن الموظف مقدم الطلب
            if ($documentRequest->branchAgent && $documentRequest->branchAgent->user_id && $documentRequest->branchAgent->user_id != $documentRequest->user_id) {
                $mainOwner = \App\Models\User::find($documentRequest->branchAgent->user_id);
                if ($mainOwner) {
                    $mainOwner->notify(new \App\Notifications\SystemNotification($title, $message, $type, $url));
                }
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in DocumentRequest update: ' . $ne->getMessage());
        }

        return response()->json($documentRequest->load(['branchAgent', 'user', 'reviewer']));
    }

    /**
     * إلغاء الوثيقة المستهدفة في قاعدة البيانات فعلياً عند موافقة الإدارة
     */
    private function cancelTargetDocument(DocumentRequest $request, $reviewerId = null)
    {
        $modelsMap = $this->getDocumentModels();
        $targetDoc = null;

        // 1. البحث عبر document_id و document_type
        if ($request->document_id && $request->document_type && isset($modelsMap[$request->document_type])) {
            $modelClass = $modelsMap[$request->document_type];
            $targetDoc = $modelClass::find($request->document_id);
        }

        // 2. البحث البديل برقم الوثيقة
        if (!$targetDoc) {
            $searchModels = [];
            if ($request->document_type && isset($modelsMap[$request->document_type])) {
                $searchModels[] = $modelsMap[$request->document_type];
            } else {
                $searchModels = array_unique(array_values($modelsMap));
            }

            foreach ($searchModels as $modelClass) {
                $field = ($modelClass === \App\Models\InternationalInsuranceDocument::class) ? 'document_number' : 'insurance_number';
                $found = $modelClass::where($field, $request->document_number)->first();
                if ($found) {
                    $targetDoc = $found;
                    break;
                }
            }
        }

        if ($targetDoc) {
            $reason = $request->cancellation_reason ?: 'طلب إلغاء معتمد من الإدارة';
            if ($request->cancellation_reason_other) {
                $reason .= ' (' . $request->cancellation_reason_other . ')';
            }
            if ($request->notes) {
                $reason .= ' - ' . $request->notes;
            }
            if ($request->request_code) {
                $reason .= ' [كود الطلب: ' . $request->request_code . ']';
            }

            $targetDoc->update([
                'is_canceled' => true,
                'canceled_at' => now(),
                'canceled_by' => $request->user_id ?? $reviewerId,
                'cancel_reason' => $reason,
            ]);

            \Illuminate\Support\Facades\Log::info("DocumentRequest #{$request->id} successfully canceled document #{$targetDoc->id} ({$request->document_number})");
        } else {
            \Illuminate\Support\Facades\Log::warning("DocumentRequest #{$request->id} accepted, but target document {$request->document_number} was not found to be canceled.");
        }
    }

    /**
     * طباعة إشعار إلغاء وثيقة رسمي
     */
    public function printCancellationNotice($id)
    {
        $request = DocumentRequest::with(['branchAgent', 'user', 'reviewer'])->findOrFail($id);
        return view('document-requests.cancellation-notice', compact('request'));
    }

    public function destroy($id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);
        $documentRequest->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }

    public function pendingCount(Request $request)
    {
        $query = DocumentRequest::where('status', 'pending');

        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && !($user->is_admin ?? false)) {
                $agent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($agent) {
                    $query->where('branch_agent_id', $agent->id);
                } else {
                    $query->where('user_id', $userId);
                }
            }
        }

        return response()->json(['count' => $query->count()]);
    }
}
