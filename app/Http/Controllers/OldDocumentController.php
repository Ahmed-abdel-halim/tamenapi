<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
                    'number_field' => 'document_number',
                    'default_prefix' => 'BKMAR',
                ],
                'medical' => [
                    'model' => ProfessionalLiabilityInsuranceDocument::class,
                    'number_field' => 'document_number',
                    'default_prefix' => 'BKMED',
                ],
                'personal_accident' => [
                    'model' => PersonalAccidentInsuranceDocument::class,
                    'number_field' => 'document_number',
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

            // 3. إنشاء كائن الموديل وتعبئة الحقول
            $document = new $modelClass();
            $document->timestamps = false; // إلغاء التحديث التلقائي للتواريخ

            // تعيين الحقول العامة للتواريخ
            $document->$numberField = $documentNumber;
            
            // في بعض الجداول اسم حقل التاريخ هو issue_date كـ timestamp
            $columns = Schema::getColumnListing($document->getTable());
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
                if (in_array($key, $columns) && !in_array($key, ['id', 'created_at', 'updated_at', 'issue_date', $numberField, 'branch_agent_id'])) {
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
                TravelInsurancePassenger::create([
                    'travel_insurance_document_id' => $document->id,
                    'is_main_passenger' => true,
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
                ]);
            } elseif ($documentType === 'resident') {
                ResidentInsurancePassenger::create([
                    'resident_insurance_document_id' => $document->id,
                    'is_main_passenger' => true,
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
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الوثيقة القديمة بنجاح بالتاريخ المحدد',
                'document' => $document
            ], 201);

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
}
