<?php

namespace App\Http\Controllers;

use App\Models\BranchAgent;
use App\Models\User;
use App\Models\InsuranceDocument;
use App\Models\TravelInsuranceDocument;
use App\Models\ResidentInsuranceDocument;
use App\Models\MarineStructureInsuranceDocument;
use App\Models\ProfessionalLiabilityInsuranceDocument;
use App\Models\PersonalAccidentInsuranceDocument;
use App\Models\InternationalInsuranceDocument;
use App\Models\SchoolStudentInsuranceDocument;
use App\Models\CargoInsuranceDocument;
use App\Models\CashInTransitInsuranceDocument;
use App\Models\MonthlyAccountClosure;
use App\Models\PaymentVoucher;
use App\Services\InsuranceTypeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BranchAgentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $branchesAgents = BranchAgent::with('user:id,username,name')
                ->orderBy('created_at', 'desc')
                ->get();
            
            return response()->json($branchesAgents);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|in:وكيل,فرع من شركة',
                'agency_name' => 'required|string',
                'agent_name' => 'required|string',
                'activity' => 'nullable|string',
                'agency_number' => 'nullable|string',
                'stamp_number' => 'nullable|string',
                'contract_date' => 'required|date',
                'contract_end_date' => 'nullable|date',
                'contract_duration' => 'nullable|string',
                'city' => 'required|string',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'nationality' => 'nullable|string',
                'national_id' => 'nullable|string|size:12',
                'identity_number' => 'nullable|string',
                'consumed_custodies' => 'nullable|string',
                'fixed_custodies' => 'nullable|string',
                'personal_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'identity_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'contract_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'username' => 'required|string|unique:users,username',
                'password' => 'required|string|min:6',
                'notes' => 'nullable|string',
                'status' => 'nullable|in:نشط,غير نشط',
                'authorized_documents' => 'nullable|string',
                'document_percentages' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // معالجة الوثائق المصرح بها والنسب
            $authorizedDocuments = [];
            $documentPercentages = [];
            if ($request->has('authorized_documents') && $request->authorized_documents) {
                $decoded = is_string($request->authorized_documents) 
                    ? json_decode($request->authorized_documents, true) 
                    : $request->authorized_documents;
                if (is_array($decoded)) {
                    $authorizedDocuments = $decoded;
                }
            }
            if ($request->has('document_percentages') && $request->document_percentages) {
                $decoded = is_string($request->document_percentages) 
                    ? json_decode($request->document_percentages, true) 
                    : $request->document_percentages;
                if (is_array($decoded)) {
                    $documentPercentages = $decoded;
                }
            }

            // إنشاء المستخدم مع الصلاحيات
            $user = User::create([
                'username' => $request->username,
                'name' => $request->agent_name,
                'password' => Hash::make($request->password),
                'is_admin' => false,
                'authorized_documents' => $authorizedDocuments,
            ]);

            // توليد الكود التلقائي
            $lastAgent = BranchAgent::orderBy('id', 'desc')->first();
            $nextNumber = $lastAgent ? (int)substr($lastAgent->code, 2) + 1 : 1;
            $code = 'BK' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

            // رفع الصور
            $personalPhoto = null;
            $identityPhoto = null;
            $contractPhoto = null;

            if ($request->hasFile('personal_photo')) {
                $personalPhoto = $request->file('personal_photo')->store('branches_agents/personal_photos', 'public');
            }
            if ($request->hasFile('identity_photo')) {
                $identityPhoto = $request->file('identity_photo')->store('branches_agents/identity_photos', 'public');
            }
            if ($request->hasFile('contract_photo')) {
                $contractPhoto = $request->file('contract_photo')->store('branches_agents/contract_photos', 'public');
            }

            // معالجة JSON للعهود
            $consumedCustodies = null;
            if ($request->has('consumed_custodies') && $request->consumed_custodies) {
                $consumedCustodies = is_string($request->consumed_custodies) 
                    ? json_decode($request->consumed_custodies, true) 
                    : $request->consumed_custodies;
            }
            
            $fixedCustodies = null;
            if ($request->has('fixed_custodies') && $request->fixed_custodies) {
                $fixedCustodies = is_string($request->fixed_custodies) 
                    ? json_decode($request->fixed_custodies, true) 
                    : $request->fixed_custodies;
            }

            // إنشاء الفرع أو الوكيل
            $branchAgent = BranchAgent::create([
                'type' => $request->type,
                'code' => $code,
                'agency_name' => $request->agency_name,
                'agent_name' => $request->agent_name,
                'activity' => $request->activity,
                'agency_number' => $request->agency_number,
                'stamp_number' => $request->stamp_number,
                'contract_date' => $request->contract_date,
                'contract_end_date' => $request->contract_end_date,
                'contract_duration' => $request->contract_duration,
                'city' => $request->city,
                'address' => $request->address,
                'phone' => $request->phone,
                'nationality' => $request->nationality,
                'national_id' => $request->national_id,
                'identity_number' => $request->identity_number,
                'consumed_custodies' => $consumedCustodies,
                'fixed_custodies' => $fixedCustodies,
                'personal_photo' => $personalPhoto,
                'identity_photo' => $identityPhoto,
                'contract_photo' => $contractPhoto,
                'user_id' => $user->id,
                'notes' => $request->notes,
                'status' => $request->status ?? 'نشط',
                'authorized_documents' => $authorizedDocuments,
                'document_percentages' => $documentPercentages,
            ]);

            DB::commit();

            return response()->json($branchAgent->load('user'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء السجل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $branchAgent = BranchAgent::with('user')->findOrFail($id);
            return response()->json($branchAgent);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'السجل غير موجود',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $branchAgent = BranchAgent::findOrFail($id);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'السجل غير موجود',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 404);
        }
        
        // معالجة consumed_custodies و fixed_custodies قبل التحقق
        $consumedCustodies = $request->input('consumed_custodies');
        $fixedCustodies = $request->input('fixed_custodies');
        
        // تحويل string إلى array إذا لزم الأمر
        if (is_string($consumedCustodies)) {
            $consumedCustodies = json_decode($consumedCustodies, true) ?: [];
        }
        if (is_string($fixedCustodies)) {
            $fixedCustodies = json_decode($fixedCustodies, true) ?: [];
        }
        
        // إذا كانت فارغة أو null، اجعلها array فارغ
        if (empty($consumedCustodies) || !is_array($consumedCustodies)) {
            $consumedCustodies = [];
        }
        if (empty($fixedCustodies) || !is_array($fixedCustodies)) {
            $fixedCustodies = [];
        }
        
        // استبدال القيم في الـ request
        $request->merge([
            'consumed_custodies' => $consumedCustodies,
            'fixed_custodies' => $fixedCustodies,
        ]);
        
        $request->validate([
            'type' => 'nullable|in:وكيل,فرع من شركة',
            'agency_name' => 'nullable|string',
            'agent_name' => 'nullable|string',
            'activity' => 'nullable|string',
            'agency_number' => 'nullable|string',
            'stamp_number' => 'nullable|string',
            'contract_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'contract_duration' => 'nullable|string',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'nationality' => 'nullable|string',
            'national_id' => 'nullable|string|size:12',
            'identity_number' => 'nullable|string',
            'consumed_custodies' => 'nullable|array',
            'consumed_custodies.*.description' => 'required_with:consumed_custodies|string',
            'consumed_custodies.*.quantity' => 'required_with:consumed_custodies|integer|min:1',
            'fixed_custodies' => 'nullable|array',
            'fixed_custodies.*.description' => 'required_with:fixed_custodies|string',
            'fixed_custodies.*.quantity' => 'required_with:fixed_custodies|integer|min:1',
            'personal_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'identity_photo' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            'contract_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'username' => 'nullable|string|unique:users,username,' . $branchAgent->user_id,
            'password' => 'nullable|string|min:6',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:نشط,غير نشط',
            'authorized_documents' => 'nullable|string',
            'document_percentages' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // تحديث بيانات المستخدم
            if ($branchAgent->user_id) {
                $user = User::find($branchAgent->user_id);
                if ($user) {
                    if ($request->has('username')) {
                        $user->username = $request->username;
                    }
                    if ($request->has('agent_name')) {
                        $user->name = $request->agent_name;
                    }
                    if ($request->has('password')) {
                        $user->password = Hash::make($request->password);
                    }
                    
                    // تحديث الصلاحيات في جدول users
                    if ($request->has('authorized_documents')) {
                        $rawValue = $request->authorized_documents;
                        
                        if ($rawValue !== null && $rawValue !== '') {
                            $decoded = is_string($rawValue) 
                                ? json_decode($rawValue, true) 
                                : $rawValue;
                            
                            if (is_array($decoded)) {
                                $user->authorized_documents = $decoded;
                            } else {
                                $user->authorized_documents = [];
                            }
                        } else {
                            $user->authorized_documents = [];
                        }
                    }
                    
                    $user->save();
                }
            }

            // تحديث الصور
            if ($request->hasFile('personal_photo')) {
                if ($branchAgent->personal_photo && Storage::disk('public')->exists($branchAgent->personal_photo)) {
                    Storage::disk('public')->delete($branchAgent->personal_photo);
                }
                $branchAgent->personal_photo = $request->file('personal_photo')->store('branches_agents/personal_photos', 'public');
            }
            if ($request->hasFile('identity_photo')) {
                if ($branchAgent->identity_photo && Storage::disk('public')->exists($branchAgent->identity_photo)) {
                    Storage::disk('public')->delete($branchAgent->identity_photo);
                }
                $branchAgent->identity_photo = $request->file('identity_photo')->store('branches_agents/identity_photos', 'public');
            }
            if ($request->hasFile('contract_photo')) {
                if ($branchAgent->contract_photo && Storage::disk('public')->exists($branchAgent->contract_photo)) {
                    Storage::disk('public')->delete($branchAgent->contract_photo);
                }
                $branchAgent->contract_photo = $request->file('contract_photo')->store('branches_agents/contract_photos', 'public');
            }

            // معالجة JSON للعهود
            $updateData = $request->only([
                'type', 'agency_name', 'agent_name', 'activity', 'agency_number',
                'stamp_number', 'contract_date', 'contract_end_date', 'contract_duration',
                'city', 'address', 'phone', 'nationality', 'national_id',
                'identity_number', 'notes', 'status'
            ]);

            if ($request->has('consumed_custodies') && $request->consumed_custodies) {
                $updateData['consumed_custodies'] = is_string($request->consumed_custodies) 
                    ? json_decode($request->consumed_custodies, true) 
                    : $request->consumed_custodies;
            }
            
            if ($request->has('fixed_custodies') && $request->fixed_custodies) {
                $updateData['fixed_custodies'] = is_string($request->fixed_custodies) 
                    ? json_decode($request->fixed_custodies, true) 
                    : $request->fixed_custodies;
            }

            // معالجة الوثائق المصرح بها والنسب
            if ($request->has('authorized_documents')) {
                $rawValue = $request->authorized_documents;
                
                if ($rawValue !== null && $rawValue !== '') {
                    $decoded = is_string($rawValue) 
                        ? json_decode($rawValue, true) 
                        : $rawValue;
                    
                    if (is_array($decoded)) {
                        // احفظ البيانات (حتى لو كانت فارغة) لأن المستخدم قد يريد حذف جميع الوثائق
                        $updateData['authorized_documents'] = $decoded;
                    } else {
                        // إذا فشل فك التشفير، احفظ array فارغ
                        $updateData['authorized_documents'] = [];
                    }
                } else {
                    // إذا كانت القيمة فارغة، احفظ array فارغ
                    $updateData['authorized_documents'] = [];
                }
            }
            // إذا لم يتم إرسال البيانات، لا نحدثها (نحتفظ بالبيانات السابقة)
            
            if ($request->has('document_percentages')) {
                $rawValue = $request->document_percentages;
                
                if ($rawValue !== null && $rawValue !== '') {
                    $decoded = is_string($rawValue) 
                        ? json_decode($rawValue, true) 
                        : $rawValue;
                    
                    if (is_array($decoded)) {
                        // احفظ البيانات (حتى لو كانت فارغة) لأن المستخدم قد يريد حذف جميع النسب
                        $updateData['document_percentages'] = $decoded;
                    } else {
                        // إذا فشل فك التشفير، احفظ array فارغ
                        $updateData['document_percentages'] = [];
                    }
                } else {
                    // إذا كانت القيمة فارغة، احفظ array فارغ
                    $updateData['document_percentages'] = [];
                }
            }
            // إذا لم يتم إرسال البيانات، لا نحدثها (نحتفظ بالبيانات السابقة)

            // تحديث البيانات مباشرة بدون array_filter للحفاظ على arrays فارغة
            $branchAgent->update($updateData);

            DB::commit();

            return response()->json($branchAgent->load('user'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'حدث خطأ أثناء تحديث السجل: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $branchAgent = BranchAgent::findOrFail($id);
            
            // حذف الصور
            if ($branchAgent->personal_photo && Storage::disk('public')->exists($branchAgent->personal_photo)) {
                Storage::disk('public')->delete($branchAgent->personal_photo);
            }
            if ($branchAgent->identity_photo && Storage::disk('public')->exists($branchAgent->identity_photo)) {
                Storage::disk('public')->delete($branchAgent->identity_photo);
            }
            if ($branchAgent->contract_photo && Storage::disk('public')->exists($branchAgent->contract_photo)) {
                Storage::disk('public')->delete($branchAgent->contract_photo);
            }

            // حذف المستخدم المرتبط
            if ($branchAgent->user_id) {
                User::where('id', $branchAgent->user_id)->delete();
            }

            $branchAgent->delete();

            DB::commit();

            return response()->json(['status' => 'deleted', 'message' => 'تم حذف السجل بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف السجل',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print contract
     */
    public function print($id)
    {
        try {
            $branchAgent = BranchAgent::with('user')->findOrFail($id);
            return view('branches-agents.print', compact('branchAgent'));
        } catch (\Exception $e) {
            abort(404, 'السجل غير موجود');
        }
    }

    /**
     * Print account report
     */
    public function accountReport(Request $request, $id)
    {
        try {
            $branchAgent = BranchAgent::with('user')->findOrFail($id);
            $type = $request->get('type', 'full'); // 'range' or 'full'
            $year = $request->get('year');
            $month = $request->get('month');
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');

            $applyCreatedAtFilter = function ($query) use ($type, $year, $month, $fromDate, $toDate) {
                if ($type === 'range' && $fromDate && $toDate) {
                    return $query->whereDate('created_at', '>=', $fromDate)
                        ->whereDate('created_at', '<=', $toDate);
                }
                if ($type === 'monthly' && $year && $month) {
                    return $query->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
                }
                return $query;
            };

            $applyPaymentDateFilter = function ($query) use ($type, $year, $month, $fromDate, $toDate) {
                if ($type === 'range' && $fromDate && $toDate) {
                    return $query->whereDate('payment_date', '>=', $fromDate)
                        ->whereDate('payment_date', '<=', $toDate);
                }
                if ($type === 'monthly' && $year && $month) {
                    return $query->whereYear('payment_date', $year)
                        ->whereMonth('payment_date', $month);
                }
                return $query;
            };

            // جلب جميع وثائق التأمين المرتبطة بالوكيل
            $insuranceDocuments = DB::table('insurance_documents')
                ->select('id', 'insurance_type', 'insurance_number', 'premium', 'total', 'phone', 'insured_name', 'created_at')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $internationalInsuranceDocuments = DB::table('international_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $travelInsuranceDocuments = DB::table('travel_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $residentInsuranceDocuments = DB::table('resident_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $marineStructureInsuranceDocuments = DB::table('marine_structure_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $professionalLiabilityInsuranceDocuments = DB::table('professional_liability_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $personalAccidentInsuranceDocuments = DB::table('personal_accident_insurance_documents')
                ->select('id', 'insurance_number', 'premium', 'total', 'phone', 'name', 'created_at')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $schoolStudentInsuranceDocuments = DB::table('school_student_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $cargoInsuranceDocuments = DB::table('cargo_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            $cashInTransitInsuranceDocuments = DB::table('cash_in_transit_insurance_documents')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyCreatedAtFilter)
                ->get();

            // جلب إيصالات القبض (Payment Vouchers) لخصمها من الرصيد
            $paymentVouchers = DB::table('payment_vouchers')
                ->where('branch_agent_id', $id)
                ->when($type === 'monthly' || $type === 'range', $applyPaymentDateFilter)
                ->get();

            // حساب النسب من document_percentages
            $documentPercentages = $branchAgent->document_percentages ?? [];
            
            // حساب المبالغ المستحقة
            $totalAmount = 0;
            $totalCompanyAmount = 0;
            $documentsByCategory = [];

            // معالجة كل نوع من الوثائق - تأمين السيارات (جميع الأنواع)
            foreach ($insuranceDocuments as $doc) {
                // تحديد نوع التأمين من insurance_type
                $insuranceType = $doc->insurance_type ?? '';
                
                // البحث عن النسبة حسب نوع التأمين المحدد
                $percentage = 0;
                
                // خريطة أنواع التأمين في قاعدة البيانات إلى مفاتيح النسب
                $insuranceTypeToPercentageKey = [
                    'تأمين إجباري سيارات' => 'تأمين سيارات', // النسبة محفوظة تحت "تأمين سيارات"
                    'تأمين سيارة جمرك' => 'تأمين سيارة جمرك',
                    'تأمين سيارات أجنبية' => 'تأمين سيارات أجنبية',
                    'تأمين طرف ثالث سيارات' => 'تأمين طرف ثالث سيارات',
                ];
                
                // البحث عن المفتاح المناسب للنسبة
                $percentageKey = $insuranceTypeToPercentageKey[$insuranceType] ?? null;
                
                if ($percentageKey && isset($documentPercentages[$percentageKey])) {
                    $percentage = $documentPercentages[$percentageKey];
                } elseif (isset($documentPercentages[$insuranceType])) {
                    // إذا كان المفتاح مطابق تماماً
                    $percentage = $documentPercentages[$insuranceType];
                } else {
                    // البحث عن النسبة من أي نوع تأمين سيارات كبديل
                    $percentage = $documentPercentages['تأمين سيارات إجباري'] ?? 
                                 $documentPercentages['تأمين سيارات'] ?? 
                                 $documentPercentages['تأمين سيارة جمرك'] ?? 
                                 $documentPercentages['تأمين سيارات أجنبية'] ?? 
                                 $documentPercentages['تأمين طرف ثالث سيارات'] ?? 0;
                }
                
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين السيارات';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => $doc->insurance_type ?? 'تأمين إجباري سيارات',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($internationalInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين سيارات دولي'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين السيارات دولي';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين السيارات دولي',
                    'document_number' => $doc->document_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($travelInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين المسافرين'] ?? 
                             $documentPercentages['تأمين زائرين ليبيا'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين المسافرين';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                // جلب اسم المؤمن من الجدول المرتبط
                $mainPassenger = DB::table('travel_insurance_passengers')
                    ->where('travel_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين المسافرين',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $mainPassenger->phone ?? '-',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($residentInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين الوافدين'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين الوافدين';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                // جلب اسم المؤمن من الجدول المرتبط
                $mainPassenger = DB::table('resident_insurance_passengers')
                    ->where('resident_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين الوافدين',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $mainPassenger->phone ?? '-',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($marineStructureInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين الهياكل البحرية'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين الهياكل البحرية';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين الهياكل البحرية',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($professionalLiabilityInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين المسؤولية المهنية (الطبية)'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين المسؤولية المهنية';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين المسؤولية المهنية (الطبية)',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($personalAccidentInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين الحوادث الشخصية'] ?? 0;
                // حساب النسبة من القسط المقرر (premium) وليس من الإجمالي (total)
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين الحوادث الشخصيه';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                // جلب اسم المؤمن من حقل name
                $insuredName = $doc->name ?? '-';
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين الحوادث الشخصية',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $insuredName,
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($schoolStudentInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين طلبة المدارس'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين طلبة المدارس';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين طلبة المدارس',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($cargoInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين البضائع'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين البضائع';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين البضائع',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            foreach ($cashInTransitInsuranceDocuments as $doc) {
                $percentage = $documentPercentages['تأمين نقل النقدية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين نقل النقدية';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'category' => 'تأمين نقل النقدية',
                    'document_number' => $doc->insurance_number ?? '-',
                    'total' => $total,
                    'company_amount' => $companyAmount,
                    'agent_amount' => $agentAmount,
                    'percentage' => $percentage,
                    'phone' => $doc->phone ?? '-',
                    'insured_name' => $doc->insured_name ?? '-',
                    'date' => $doc->created_at ?? null,
                ];
            }

            // ترتيب الوثائق داخل كل فئة حسب التاريخ
            foreach ($documentsByCategory as $category => $docs) {
                usort($documentsByCategory[$category], function ($a, $b) {
                    $dateA = $a['date'] ? strtotime($a['date']) : 0;
                    $dateB = $b['date'] ? strtotime($b['date']) : 0;
                    return $dateB - $dateA;
                });
            }
            
            $grandTotal = $totalCompanyAmount + $totalAmount;
            // النسبة في الملخص: النسبة الأكثر تكراراً بين الوثائق (نسبة العمولة الفعلية) وليس المتوسط
            $percentagesCount = [];
            foreach ($documentsByCategory as $docs) {
                foreach ($docs as $doc) {
                    $pct = (float) ($doc['percentage'] ?? 0);
                    $key = number_format($pct, 2);
                    $percentagesCount[$key] = ($percentagesCount[$key] ?? 0) + 1;
                }
            }
            $agentPercentage = 0;
            if (!empty($percentagesCount)) {
                $agentPercentage = (float) array_search(max($percentagesCount), $percentagesCount);
            }
            $companyPercentage = 100 - $agentPercentage;
            
            // حساب المبالغ المتبقية والمدفوعة والمستحقة
            // القيمة المستحقة دائماً = القيمة للشركة (المبلغ الأصلي المستحق)
            $dueAmount = $totalCompanyAmount;
            
            // القيم الافتراضية
            $remainingAmount = $totalCompanyAmount; // المتبقي = القيمة للشركة (افتراضي)
            $paidAmount = 0; // المدفوع (افتراضي)
            
            if (($type === 'monthly' && $year && $month) || ($type === 'range' && $fromDate && $toDate)) {
                // مجموع الإيصالات للشهر الحالي
                $paidVouchersTotal = $paymentVouchers->sum('amount');
                $paidAmount = $paidVouchersTotal;
                
                if ($type === 'monthly' && $year && $month) {
                    // كشف حساب شهري: البحث عن إغلاق محفوظ للشهر المختار
                    $closure = MonthlyAccountClosure::where('branch_agent_id', $id)
                        ->where('year', $year)
                        ->where('month', $month)
                        ->first();
                    
                    if ($closure) {
                        // إذا كان هناك إغلاق يدوي، نجمع قيمته مع الإيصالات
                        $paidAmount += $closure->paid_amount;
                        $remainingAmount = max(0, $dueAmount - $paidAmount);
                    } else {
                        $remainingAmount = max(0, $dueAmount - $paidAmount);
                    }
                } else {
                    // كشف بفترة مخصصة: نعتمد مجموع الإيصالات داخل الفترة
                    $remainingAmount = max(0, $dueAmount - $paidAmount);
                }
            } elseif ($type === 'full') {
                // مجموع كل الإيصالات الصادرة للوكيل
                $totalPaidVouchers = $paymentVouchers->sum('amount');
                
                // مجموع كل الإغلاقات المحفوظة
                $closures = MonthlyAccountClosure::where('branch_agent_id', $id)->get();
                $totalPaidClosures = $closures->sum('paid_amount');
                
                $paidAmount = $totalPaidVouchers + $totalPaidClosures;
                
                // المتبقي = القيمة المستحقة - الإجمالي المدفوع
                $remainingAmount = max(0, $dueAmount - $paidAmount);
            }

            $reportData = [
                'branchAgent' => $branchAgent,
                'type' => $type,
                'year' => $year,
                'month' => $month,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'documentsByCategory' => $documentsByCategory,
                'totalAmount' => $totalAmount,
                'totalCompanyAmount' => $totalCompanyAmount,
                'grandTotal' => $grandTotal,
                'companyPercentage' => $companyPercentage,
                'agentPercentage' => $agentPercentage,
                'remainingAmount' => $remainingAmount,
                'paidAmount' => $paidAmount,
                'dueAmount' => $dueAmount,
            ];

            return view('branches-agents.account-report', $reportData);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response('<html><body><h1>الوكيل غير موجود</h1></body></html>', 404)
                ->header('Content-Type', 'text/html; charset=utf-8');
        } catch (\Exception $e) {
            return response('<html><body><h1>حدث خطأ أثناء جلب التقرير</h1><p>' . (config('app.debug') ? htmlspecialchars($e->getMessage()) : '') . '</p></body></html>', 500)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }
    }

    /**
     * Get monthly account closure data
     */
    public function getMonthlyAccountClosure(Request $request)
    {
        try {
            $branchAgentId = $request->get('branch_agent_id');
            $type = $request->get('type', 'monthly');
            $year = $request->get('year');
            $month = $request->get('month');
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $insuranceTypeFilter = $request->get('insurance_type'); // نوع التأمين المحدد للفلترة

            if (
                !$branchAgentId ||
                ($type === 'monthly' && (!$year || !$month)) ||
                ($type === 'range' && (!$fromDate || !$toDate))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تحديد الوكيل وفترة البحث',
                    'error' => 'Missing required parameters'
                ], 400);
            }

            $branchAgent = BranchAgent::with('user')->findOrFail($branchAgentId);
            $documentPercentages = $branchAgent->document_percentages ?? [];

            $documents = [];
            $totalAmount = 0;
            $totalCompanyAmount = 0;
            
            // دالة مساعدة للتحقق من نوع التأمين باستخدام Service
            $shouldIncludeDocument = function($docInsuranceTypeLabel) use ($insuranceTypeFilter) {
                return InsuranceTypeService::matchesFilter($docInsuranceTypeLabel, $insuranceTypeFilter ?? 'all');
            };

            $applyCreatedAtFilter = function ($query) use ($type, $year, $month, $fromDate, $toDate) {
                if ($type === 'range' && $fromDate && $toDate) {
                    return $query->whereDate('created_at', '>=', $fromDate)
                        ->whereDate('created_at', '<=', $toDate);
                }
                return $query->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
            };

            // جلب جميع وثائق التأمين للشهر المحدد
            $insuranceDocuments = DB::table('insurance_documents')
                ->select('id', 'insurance_type', 'insurance_number', 'premium', 'total', 'phone', 'insured_name', 'created_at', 'issue_date')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($insuranceDocuments as $doc) {
                $insuranceType = $doc->insurance_type ?? '';
                $percentage = 0;
                
                $insuranceTypeToPercentageKey = [
                    'تأمين إجباري سيارات' => 'تأمين سيارات',
                    'تأمين سيارة جمرك' => 'تأمين سيارة جمرك',
                    'تأمين سيارات أجنبية' => 'تأمين سيارات أجنبية',
                    'تأمين طرف ثالث سيارات' => 'تأمين طرف ثالث سيارات',
                ];
                
                $percentageKey = $insuranceTypeToPercentageKey[$insuranceType] ?? null;
                if ($percentageKey && isset($documentPercentages[$percentageKey])) {
                    $percentage = $documentPercentages[$percentageKey];
                } elseif (isset($documentPercentages[$insuranceType])) {
                    $percentage = $documentPercentages[$insuranceType];
                } else {
                    $percentage = $documentPercentages['تأمين سيارات إجباري'] ?? 
                                 $documentPercentages['تأمين سيارات'] ?? 
                                 $documentPercentages['تأمين سيارة جمرك'] ?? 
                                 $documentPercentages['تأمين سيارات أجنبية'] ?? 
                                 $documentPercentages['تأمين طرف ثالث سيارات'] ?? 0;
                }
                
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument($insuranceType)) {
                    continue;
                }
                
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين السيارات',
                    'category' => $doc->category ?? $insuranceType,
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // International Insurance
            $internationalDocs = DB::table('international_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($internationalDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين السيارات الدولي')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين سيارات دولي'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين السيارات الدولي',
                    'category' => 'تأمين السيارات الدولي',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->document_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Travel Insurance
            $travelDocs = DB::table('travel_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($travelDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين المسافرين')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين المسافرين'] ?? 
                             $documentPercentages['تأمين زائرين ليبيا'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $mainPassenger = DB::table('travel_insurance_passengers')
                    ->where('travel_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $documents[] = [
                    'insurance_type' => 'تأمين المسافرين',
                    'category' => 'تأمين المسافرين',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'phone' => $mainPassenger->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Resident Insurance
            $residentDocs = DB::table('resident_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($residentDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الوافدين')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الوافدين'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $mainPassenger = DB::table('resident_insurance_passengers')
                    ->where('resident_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $documents[] = [
                    'insurance_type' => 'تأمين الوافدين',
                    'category' => 'تأمين الوافدين',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'phone' => $mainPassenger->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Marine Structure Insurance
            $marineDocs = DB::table('marine_structure_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($marineDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الهياكل البحرية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الهياكل البحرية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين الهياكل البحرية',
                    'category' => 'تأمين الهياكل البحرية',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Professional Liability Insurance
            $professionalDocs = DB::table('professional_liability_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($professionalDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين المسؤولية المهنية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين المسؤولية المهنية (الطبية)'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين المسؤولية المهنية',
                    'category' => 'تأمين المسؤولية المهنية (الطبية)',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Personal Accident Insurance
            $personalAccidentDocs = DB::table('personal_accident_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($personalAccidentDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الحوادث الشخصية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الحوادث الشخصية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $insuredName = $doc->name ?? $doc->insured_name ?? '-';
                
                $documents[] = [
                    'insurance_type' => 'تأمين الحوادث الشخصية',
                    'category' => 'تأمين الحوادث الشخصية',
                    'insured_name' => $insuredName,
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // School Student Insurance
            $schoolStudentDocs = DB::table('school_student_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($schoolStudentDocs as $doc) {
                if (!$shouldIncludeDocument('تأمين طلبة المدارس')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين طلبة المدارس'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين طلبة المدارس',
                    'category' => 'تأمين طلبة المدارس',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Cargo Insurance
            $cargoDocs = DB::table('cargo_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($cargoDocs as $doc) {
                if (!$shouldIncludeDocument('تأمين البضائع')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين البضائع'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين البضائع',
                    'category' => 'تأمين البضائع',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Cash In Transit Insurance
            $cashInTransitDocs = DB::table('cash_in_transit_insurance_documents')
                ->where('branch_agent_id', $branchAgentId)
                ->where($applyCreatedAtFilter)
                ->get();

            foreach ($cashInTransitDocs as $doc) {
                if (!$shouldIncludeDocument('تأمين نقل النقدية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين نقل النقدية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $documents[] = [
                    'insurance_type' => 'تأمين نقل النقدية',
                    'category' => 'تأمين نقل النقدية',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // ترتيب الوثائق حسب التاريخ
            usort($documents, function ($a, $b) {
                $dateA = $a['date'] ? strtotime($a['date']) : 0;
                $dateB = $b['date'] ? strtotime($b['date']) : 0;
                return $dateB - $dateA;
            });

            // التحقق من وجود إغلاق محفوظ مسبقاً
            $existingClosure = null;
            if ($type === 'monthly') {
                $existingClosure = MonthlyAccountClosure::where('branch_agent_id', $branchAgentId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();
            }

            // جلب إيصالات القبض (Payment Vouchers) الصادرة في هذا الشهر
            $paymentVouchersMonthQuery = DB::table('payment_vouchers')
                ->where('branch_agent_id', $branchAgentId);
            if ($type === 'range' && $fromDate && $toDate) {
                $paymentVouchersMonthQuery
                    ->whereDate('payment_date', '>=', $fromDate)
                    ->whereDate('payment_date', '<=', $toDate);
            } else {
                $paymentVouchersMonthQuery
                    ->whereYear('payment_date', $year)
                    ->whereMonth('payment_date', $month);
            }
            $paymentVouchersMonth = $paymentVouchersMonthQuery->sum('amount');
            
            // جلب إجمالي إيصالات القبض (Payment Vouchers) للوكيل عبر كل الزمن
            $paymentVouchersAllTime = DB::table('payment_vouchers')
                ->where('branch_agent_id', $branchAgentId)
                ->sum('amount');

            return response()->json([
                'success' => true,
                'branch_agent' => [
                    'id' => $branchAgent->id,
                    'code' => $branchAgent->code,
                    'agency_name' => $branchAgent->agency_name,
                    'agent_name' => $branchAgent->agent_name,
                ],
                'documents' => $documents,
                'summary' => [
                    'total_agent_amount' => $totalAmount,
                    'total_company_amount' => $totalCompanyAmount,
                    'due_amount' => $totalCompanyAmount, // القيمة المستحقة = القيمة للشركة
                    'paid_vouchers_amount' => $paymentVouchersMonth, // إجمالي الإيصالات الصادرة في هذا الشهر
                    'total_vouchers_all_time' => $paymentVouchersAllTime, // إجمالي الإيصالات عبر كل الزمن
                ],
                'closure' => $existingClosure ? [
                    'paid_amount' => $existingClosure->paid_amount,
                    'remaining_amount' => $existingClosure->remaining_amount,
                    'created_at' => $existingClosure->created_at,
                    'updated_at' => $existingClosure->updated_at,
                ] : null,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'الوكيل غير موجود',
                'error' => 'Branch agent not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Get dashboard statistics for insurance documents
     */
    public function getStatistics(Request $request)
    {
        try {
            $userId = $request->header('X-User-Id') ?? $request->query('user_id');
            $isAdmin = false;
            $branchAgentId = null;

            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $isAdmin = $user->is_admin ?? false;
                        if (!$isAdmin) {
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            $statistics = [
                'insurance_documents' => 0,
                'travel_insurance_documents' => 0,
                'resident_insurance_documents' => 0,
                'marine_structure_insurance_documents' => 0,
                'professional_liability_insurance_documents' => 0,
                'personal_accident_insurance_documents' => 0,
                'international_insurance_documents' => 0,
                'school_student_insurance_documents' => 0,
                'cargo_insurance_documents' => 0,
                'cash_in_transit_insurance_documents' => 0,
            ];

            // Insurance Documents (Car Insurance)
            $insuranceQuery = InsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $insuranceQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['insurance_documents'] = $insuranceQuery->active()->count();

            // Travel Insurance Documents
            $travelQuery = TravelInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $travelQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['travel_insurance_documents'] = $travelQuery->active()->count();

            // Resident Insurance Documents
            $residentQuery = ResidentInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $residentQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['resident_insurance_documents'] = $residentQuery->active()->count();

            // Marine Structure Insurance Documents
            $marineQuery = MarineStructureInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $marineQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['marine_structure_insurance_documents'] = $marineQuery->active()->count();

            // Professional Liability Insurance Documents
            $professionalQuery = ProfessionalLiabilityInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $professionalQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['professional_liability_insurance_documents'] = $professionalQuery->active()->count();

            // Personal Accident Insurance Documents
            $personalQuery = PersonalAccidentInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $personalQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['personal_accident_insurance_documents'] = $personalQuery->active()->count();

            // International Insurance Documents
            $internationalQuery = InternationalInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $internationalQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['international_insurance_documents'] = $internationalQuery->active()->count();

            // School Student Insurance Documents
            $schoolQuery = SchoolStudentInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $schoolQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['school_student_insurance_documents'] = $schoolQuery->count();

            // Cargo Insurance Documents
            $cargoStatQuery = CargoInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $cargoStatQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['cargo_insurance_documents'] = $cargoStatQuery->count();

            // Cash In Transit Insurance Documents
            $cashStatQuery = CashInTransitInsuranceDocument::query();
            if (!$isAdmin && $branchAgentId) {
                $cashStatQuery->where('branch_agent_id', $branchAgentId);
            }
            $statistics['cash_in_transit_insurance_documents'] = $cashStatQuery->count();

            return response()->json($statistics);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الإحصائيات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Get latest 5 insurance documents
     */
    public function getLatestDocuments(Request $request)
    {
        try {
            $userId = $request->header('X-User-Id') ?? $request->query('user_id');
            $isAdmin = false;
            $branchAgentId = null;

            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $isAdmin = $user->is_admin ?? false;
                        if (!$isAdmin) {
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            $allDocuments = collect();

            // Insurance Documents (Car Insurance)
            $insuranceQuery = InsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $insuranceQuery->where('branch_agent_id', $branchAgentId);
            }
            $insuranceDocs = $insuranceQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => $doc->insurance_type ?? 'تأمين سيارات',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'insurance',
                    ];
                });
            $allDocuments = $allDocuments->concat($insuranceDocs);

            // Travel Insurance Documents
            $travelQuery = TravelInsuranceDocument::with(['branchAgent', 'passengers']);
            if (!$isAdmin && $branchAgentId) {
                $travelQuery->where('branch_agent_id', $branchAgentId);
            }
            $travelDocs = $travelQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    // جمع أسماء جميع الركاب من name_ar
                    $allNames = $doc->passengers->pluck('name_ar')->filter(function($name) {
                        return !empty($name) && trim($name) !== '';
                    })->unique()->values();
                    $insuredName = $allNames->isNotEmpty() ? $allNames->join('، ') : '-';
                    // جمع أرقام الهواتف
                    $allPhones = $doc->passengers->pluck('phone')->filter(function($phone) {
                        return !empty($phone) && trim($phone) !== '';
                    })->unique()->values();
                    $phone = $allPhones->isNotEmpty() ? $allPhones->first() : '-';
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $insuredName,
                        'phone' => $phone,
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين المسافرين',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'travel',
                    ];
                });
            $allDocuments = $allDocuments->concat($travelDocs);

            // Resident Insurance Documents
            $residentQuery = ResidentInsuranceDocument::with(['branchAgent', 'passengers']);
            if (!$isAdmin && $branchAgentId) {
                $residentQuery->where('branch_agent_id', $branchAgentId);
            }
            $residentDocs = $residentQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    // جمع أسماء جميع الركاب من name_ar
                    $allNames = $doc->passengers->pluck('name_ar')->filter(function($name) {
                        return !empty($name) && trim($name) !== '';
                    })->unique()->values();
                    $insuredName = $allNames->isNotEmpty() ? $allNames->join('، ') : '-';
                    // جمع أرقام الهواتف
                    $allPhones = $doc->passengers->pluck('phone')->filter(function($phone) {
                        return !empty($phone) && trim($phone) !== '';
                    })->unique()->values();
                    $phone = $allPhones->isNotEmpty() ? $allPhones->first() : '-';
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $insuredName,
                        'phone' => $phone,
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين الوافدين',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'resident',
                    ];
                });
            $allDocuments = $allDocuments->concat($residentDocs);

            // Marine Structure Insurance Documents
            $marineQuery = MarineStructureInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $marineQuery->where('branch_agent_id', $branchAgentId);
            }
            $marineDocs = $marineQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين الهياكل البحرية',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'marine',
                    ];
                });
            $allDocuments = $allDocuments->concat($marineDocs);

            // Professional Liability Insurance Documents
            $professionalQuery = ProfessionalLiabilityInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $professionalQuery->where('branch_agent_id', $branchAgentId);
            }
            $professionalDocs = $professionalQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين المسؤولية المهنية (الطبية)',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'professional',
                    ];
                });
            $allDocuments = $allDocuments->concat($professionalDocs);

            // Personal Accident Insurance Documents
            $personalQuery = PersonalAccidentInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $personalQuery->where('branch_agent_id', $branchAgentId);
            }
            $personalDocs = $personalQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين الحوادث الشخصية',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'personal',
                    ];
                });
            $allDocuments = $allDocuments->concat($personalDocs);

            // International Insurance Documents
            $internationalQuery = InternationalInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $internationalQuery->where('branch_agent_id', $branchAgentId);
            }
            $internationalDocs = $internationalQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->document_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين السيارات الدولي',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'international',
                    ];
                });
            $allDocuments = $allDocuments->concat($internationalDocs);

            // School Student Insurance Documents
            $schoolLatestQuery = SchoolStudentInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $schoolLatestQuery->where('branch_agent_id', $branchAgentId);
            }
            $schoolLatestDocs = $schoolLatestQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين طلبة المدارس',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'school',
                    ];
                });
            $allDocuments = $allDocuments->concat($schoolLatestDocs);

            // Cargo Insurance Documents
            $cargoLatestQuery = CargoInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $cargoLatestQuery->where('branch_agent_id', $branchAgentId);
            }
            $cargoLatestDocs = $cargoLatestQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين البضائع',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'cargo',
                    ];
                });
            $allDocuments = $allDocuments->concat($cargoLatestDocs);

            // Cash In Transit Insurance Documents
            $cashLatestQuery = CashInTransitInsuranceDocument::with('branchAgent');
            if (!$isAdmin && $branchAgentId) {
                $cashLatestQuery->where('branch_agent_id', $branchAgentId);
            }
            $cashLatestDocs = $cashLatestQuery->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($doc) use ($isAdmin) {
                    return [
                        'id' => $doc->id,
                        'insurance_number' => $doc->insurance_number ?? '-',
                        'insured_name' => $doc->insured_name ?? '-',
                        'phone' => $doc->phone ?? '-',
                        'total' => $doc->total ?? 0,
                        'premium' => $doc->premium ?? 0,
                        'insurance_type' => 'تأمين نقل النقدية',
                        'agency_name' => $isAdmin && $doc->branchAgent ? ($doc->branchAgent->agency_name ?? '-') : null,
                        'created_at' => $doc->created_at,
                        'issue_date' => $doc->issue_date ?? $doc->created_at,
                        'type' => 'cash',
                    ];
                });
            $allDocuments = $allDocuments->concat($cashLatestDocs);

            // ترتيب جميع الوثائق حسب التاريخ (الأحدث أولاً) وأخذ آخر 5
            $latestDocuments = $allDocuments
                ->sortByDesc('created_at')
                ->take(5)
                ->values()
                ->map(function ($doc) {
                    $issueDate = $doc['issue_date'] ?? $doc['created_at'];
                    return [
                        'id' => $doc['id'],
                        'insurance_number' => $doc['insurance_number'],
                        'issue_date' => $issueDate ? ($issueDate instanceof \Carbon\Carbon ? $issueDate->format('Y-m-d') : (is_string($issueDate) ? date('Y-m-d', strtotime($issueDate)) : null)) : null,
                        'insured_name' => $doc['insured_name'],
                        'phone' => $doc['phone'],
                        'total' => (float) ($doc['total'] ?? $doc['premium'] ?? 0),
                        'insurance_type' => $doc['insurance_type'],
                        'agency_name' => $doc['agency_name'],
                        'created_at' => $doc['created_at'] ? $doc['created_at']->format('Y-m-d H:i:s') : null,
                        'type' => $doc['type'],
                    ];
                });

            return response()->json($latestDocuments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الوثائق',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Save monthly account closure
     */
    public function saveMonthlyAccountClosure(Request $request)
    {
        try {
            $request->validate([
                'branch_agent_id' => 'required|exists:branches_agents,id',
                'year' => 'required|integer|min:2020|max:2100',
                'month' => 'required|integer|min:1|max:12',
                'due_amount' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'remaining_amount' => 'required|numeric|min:0',
                'documents_data' => 'nullable|array',
                'notes' => 'nullable|string',
            ]);

            $closure = MonthlyAccountClosure::updateOrCreate(
                [
                    'branch_agent_id' => $request->branch_agent_id,
                    'year' => $request->year,
                    'month' => $request->month,
                ],
                [
                    'due_amount' => $request->due_amount,
                    'paid_amount' => $request->paid_amount,
                    'remaining_amount' => $request->remaining_amount,
                    'documents_data' => $request->documents_data ?? [],
                    'notes' => $request->notes,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ البيانات بنجاح',
                'data' => $closure->load('branchAgent'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print monthly account closure
     */
    public function printMonthlyAccountClosure(Request $request, $id)
    {
        try {
            $year = $request->get('year');
            $month = $request->get('month');
            $insuranceTypeFilter = $request->get('insurance_type'); // نوع التأمين المحدد للفلترة

            if (!$year || !$month) {
                abort(400, 'يجب تحديد السنة والشهر');
            }

            $branchAgent = BranchAgent::with('user')->findOrFail($id);
            $documentPercentages = $branchAgent->document_percentages ?? [];

            $documentsByCategory = [];
            $totalAmount = 0;
            $totalCompanyAmount = 0;
            
            // دالة مساعدة للتحقق من نوع التأمين باستخدام Service
            $shouldIncludeDocument = function($docInsuranceTypeLabel) use ($insuranceTypeFilter) {
                return InsuranceTypeService::matchesFilter($docInsuranceTypeLabel, $insuranceTypeFilter ?? 'all');
            };

            // جلب جميع وثائق التأمين للشهر المحدد (نفس منطق getMonthlyAccountClosure)
            $insuranceDocuments = DB::table('insurance_documents')
                ->select('id', 'insurance_type', 'insurance_number', 'premium', 'total', 'phone', 'insured_name', 'created_at', 'issue_date')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($insuranceDocuments as $doc) {
                $insuranceType = $doc->insurance_type ?? '';
                
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument($insuranceType)) {
                    continue;
                }
                
                $percentage = 0;
                
                $insuranceTypeToPercentageKey = [
                    'تأمين إجباري سيارات' => 'تأمين سيارات',
                    'تأمين سيارة جمرك' => 'تأمين سيارة جمرك',
                    'تأمين سيارات أجنبية' => 'تأمين سيارات أجنبية',
                    'تأمين طرف ثالث سيارات' => 'تأمين طرف ثالث سيارات',
                ];
                
                $percentageKey = $insuranceTypeToPercentageKey[$insuranceType] ?? null;
                if ($percentageKey && isset($documentPercentages[$percentageKey])) {
                    $percentage = $documentPercentages[$percentageKey];
                } elseif (isset($documentPercentages[$insuranceType])) {
                    $percentage = $documentPercentages[$insuranceType];
                } else {
                    $percentage = $documentPercentages['تأمين سيارات إجباري'] ?? 
                                 $documentPercentages['تأمين سيارات'] ?? 
                                 $documentPercentages['تأمين سيارة جمرك'] ?? 
                                 $documentPercentages['تأمين سيارات أجنبية'] ?? 
                                 $documentPercentages['تأمين طرف ثالث سيارات'] ?? 0;
                }
                
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                // استخدام insurance_type كـ category للطباعة
                $category = $insuranceType; // مثل "تأمين إجباري سيارات"
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين السيارات',
                    'category' => $insuranceType,
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // International Insurance
            $internationalDocs = DB::table('international_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($internationalDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين السيارات الدولي')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين سيارات دولي'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين السيارات دولي';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين السيارات الدولي',
                    'category' => 'تأمين السيارات الدولي',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->document_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Travel Insurance
            $travelDocs = DB::table('travel_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($travelDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين المسافرين')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين المسافرين'] ?? 
                             $documentPercentages['تأمين زائرين ليبيا'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $mainPassenger = DB::table('travel_insurance_passengers')
                    ->where('travel_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $category = 'تأمين المسافرين';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين المسافرين',
                    'category' => 'تأمين المسافرين',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'phone' => $mainPassenger->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Resident Insurance
            $residentDocs = DB::table('resident_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($residentDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الوافدين')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الوافدين'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $mainPassenger = DB::table('resident_insurance_passengers')
                    ->where('resident_insurance_document_id', $doc->id)
                    ->where('is_main_passenger', true)
                    ->first();
                
                $category = 'تأمين الوافدين';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين الوافدين',
                    'category' => 'تأمين الوافدين',
                    'insured_name' => $mainPassenger->name_ar ?? '-',
                    'phone' => $mainPassenger->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Marine Structure Insurance
            $marineDocs = DB::table('marine_structure_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($marineDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الهياكل البحرية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الهياكل البحرية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين الهياكل البحرية';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين الهياكل البحرية',
                    'category' => 'تأمين الهياكل البحرية',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Professional Liability Insurance
            $professionalDocs = DB::table('professional_liability_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($professionalDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين المسؤولية المهنية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين المسؤولية المهنية (الطبية)'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $category = 'تأمين المسؤولية المهنية';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين المسؤولية المهنية',
                    'category' => 'تأمين المسؤولية المهنية (الطبية)',
                    'insured_name' => $doc->insured_name ?? '-',
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // Personal Accident Insurance
            $personalAccidentDocs = DB::table('personal_accident_insurance_documents')
                ->where('branch_agent_id', $id)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($personalAccidentDocs as $doc) {
                // التحقق من نوع التأمين قبل الإضافة
                if (!$shouldIncludeDocument('تأمين الحوادث الشخصية')) {
                    continue;
                }
                
                $percentage = $documentPercentages['تأمين الحوادث الشخصية'] ?? 0;
                $premium = $doc->premium ?? 0;
                $total = $doc->total ?? 0;
                $agentAmount = $premium * ($percentage / 100);
                $companyAmount = $total - $agentAmount;
                $totalAmount += $agentAmount;
                $totalCompanyAmount += $companyAmount;
                
                $insuredName = $doc->name ?? $doc->insured_name ?? '-';
                
                $category = 'تأمين الحوادث الشخصيه';
                if (!isset($documentsByCategory[$category])) {
                    $documentsByCategory[$category] = [];
                }
                
                $documentsByCategory[$category][] = [
                    'insurance_type' => 'تأمين الحوادث الشخصية',
                    'category' => 'تأمين الحوادث الشخصية',
                    'insured_name' => $insuredName,
                    'phone' => $doc->phone ?? '-',
                    'insurance_code' => $doc->insurance_number ?? '-',
                    'insurance_value' => $total,
                    'agent_percentage' => $percentage,
                    'agent_amount' => $agentAmount,
                    'company_amount' => $companyAmount,
                    'date' => $doc->issue_date ?? $doc->created_at ?? null,
                ];
            }

            // ترتيب الوثائق حسب التاريخ
            foreach ($documentsByCategory as $category => $docs) {
                usort($documentsByCategory[$category], function ($a, $b) {
                    $dateA = $a['date'] ? strtotime($a['date']) : 0;
                    $dateB = $b['date'] ? strtotime($b['date']) : 0;
                    return $dateB - $dateA;
                });
            }

            $grandTotal = $totalCompanyAmount + $totalAmount;
            $companyPercentage = $grandTotal > 0 ? ($totalCompanyAmount / $grandTotal) * 100 : 0;
            $agentPercentage = $grandTotal > 0 ? ($totalAmount / $grandTotal) * 100 : 0;

            // حساب المبالغ المتبقية والمدفوعة والمستحقة
            $dueAmount = $totalCompanyAmount;
            
            // جلب إيصالات القبض (Payment Vouchers) لخصمها من الرصيد
            $paymentVouchersTotal = DB::table('payment_vouchers')
                ->where('branch_agent_id', $id)
                ->whereYear('payment_date', $year)
                ->whereMonth('payment_date', $month)
                ->sum('amount');

            $paidAmount = $paymentVouchersTotal;
            $remainingAmount = max(0, $dueAmount - $paidAmount);
            
            // التحقق من وجود إغلاق محفوظ
            $closure = MonthlyAccountClosure::where('branch_agent_id', $id)
                ->where('year', $year)
                ->where('month', $month)
                ->first();
            
            if ($closure) {
                // نجمع المبالغ المدفوعة في الإغلاق مع الإيصالات
                $paidAmount += $closure->paid_amount;
                $remainingAmount = max(0, $dueAmount - $paidAmount);
            }

            return view('branches-agents.monthly-account-closure-print', [
                'branchAgent' => $branchAgent,
                'year' => $year,
                'month' => $month,
                'documentsByCategory' => $documentsByCategory,
                'totalAmount' => $totalAmount,
                'totalCompanyAmount' => $totalCompanyAmount,
                'grandTotal' => $grandTotal,
                'companyPercentage' => $companyPercentage,
                'agentPercentage' => $agentPercentage,
                'remainingAmount' => $remainingAmount,
                'paidAmount' => $paidAmount,
                'dueAmount' => $dueAmount,
            ]);
        } catch (\Exception $e) {
            abort(404, 'السجل غير موجود');
        }
    }

    /**
     * Get monthly account closures report (list of agents who paid)
     */
    public function getMonthlyAccountClosuresReport(Request $request)
    {
        try {
            $type = $request->get('type', 'monthly');
            $year = $request->get('year');
            $month = $request->get('month');
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');

            $query = MonthlyAccountClosure::with('branchAgent:id,code,agency_name,agent_name');

            if ($type === 'range' && $fromDate && $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)
                    ->whereDate('created_at', '<=', $toDate);
            } else {
                if ($year) {
                    $query->where('year', $year);
                }

                if ($month) {
                    $query->where('month', $month);
                }
            }

            $closures = $query->orderBy('created_at', 'desc')->get()->map(function($closure) {
                // جلب إجمالي إيصالات القبض لهذا الوكيل في هذا الشهر والسنة
                $vouchersTotal = DB::table('payment_vouchers')
                    ->where('branch_agent_id', $closure->branch_agent_id)
                    ->whereYear('payment_date', $closure->year)
                    ->whereMonth('payment_date', $closure->month)
                    ->sum('amount');
                
                // القيمة المدفوعة الكلية = المسجلة يدوياً + إجمالي الإيصالات
                $closure->paid_amount = (float)$closure->paid_amount + (float)$vouchersTotal;
                $closure->remaining_amount = max(0, (float)$closure->due_amount - (float)$closure->paid_amount);
                
                return $closure;
            });

            return response()->json([
                'success' => true,
                'data' => $closures,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
