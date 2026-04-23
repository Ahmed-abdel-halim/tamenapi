<?php

namespace App\Http\Controllers;

use App\Models\MarineStructureInsuranceDocument;
use App\Models\MarineStructureEngine;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MarineStructureInsuranceDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // الحصول على المستخدم الحالي من header أو query parameter
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
                            // إذا لم يكن admin، احصل على branch_agent_id من المستخدم
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            // بناء الاستعلام
            $query = MarineStructureInsuranceDocument::with(['registrationAuthority.city', 'engines', 'branchAgent']);
            if ($request->boolean('archived')) {
                $query->archived();
            } else {
                $query->active();
            }

            // إذا لم يكن admin، قم بتصفية الوثائق حسب branch_agent_id
            if (!$isAdmin && $branchAgentId) {
                $query->where('branch_agent_id', $branchAgentId);
            }

            // إضافة ميزة البحث
            $search = $request->query('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('insurance_number', 'like', "%{$search}%")
                      ->orWhere('insured_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('vessel_name', 'like', "%{$search}%");
                });
            }

            // فلتر الوكيل (للادمن)
            if ($isAdmin && $request->has('branch_agent_id')) {
                $query->where('branch_agent_id', $request->query('branch_agent_id'));
            }

            // فلاتر التاريخ (السنة، الشهر، اليوم)
            if ($request->has('year')) {
                $query->whereYear('issue_date', $request->query('year'));
            }
            if ($request->has('month')) {
                $query->whereMonth('issue_date', $request->query('month'));
            }
            if ($request->has('day')) {
                $query->whereDay('issue_date', $request->query('day'));
            }

            $perPage = $request->query('per_page', 10);
            $documents = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $documents->getCollection()->transform(function ($document) use ($isAdmin) {
                // إضافة اسم الوكالة للادمن فقط
                if ($isAdmin) {
                    $document->agency_name = $document->branchAgent ? ($document->branchAgent->agency_name ?? null) : null;
                } else {
                    $document->agency_name = null;
                }
                
                return $document;
            });
            
            return response()->json($documents);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@index: ' . $e->getMessage());
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
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'required|in:سنة (365 يوم),سنتين (730 يوم)',
                'structure_type' => 'required|in:القوارب الشخصية والدراجات,الآلات والرافعات البحرية,قوارب الصيد',
                'license_type' => 'nullable|in:خاص,صناعي,تجاري',
                'license_purpose' => 'nullable|in:قارب تجاري,قارب حرفي,قارب الترولة,قارب الشباك السينية,قارب الخيوط السنارية,قارب الصيد بالفخ',
                'vessel_name' => 'nullable|string|max:255',
                'registration_code' => 'nullable|string|max:255',
                'registration_date' => 'nullable|date',
                'port' => 'nullable|string|max:255',
                'registration_authority_id' => 'nullable|exists:plates,id',
                'plate_number' => 'nullable|string|max:255',
                'hull_number' => 'nullable|string|max:255',
                'manufacturing_material' => 'nullable|string|max:255',
                'length' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'depth' => 'nullable|numeric|min:0',
                'manufacturing_year' => 'nullable|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:255',
                'fuel_tank_capacity' => 'nullable|numeric|min:0',
                'passenger_count' => 'nullable|integer|min:0',
                'load_capacity' => 'nullable|numeric|min:0',
                'insured_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'license_number' => 'nullable|string|max:255',
                'engines' => 'nullable|array',
                'engines.*.engine_type' => 'required|in:main,auxiliary',
                'engines.*.engine_model' => 'nullable|string|max:255',
                'engines.*.fuel_type' => 'nullable|in:بنزين Gasoline,ديزل Diesel,كهرباء,غاز طبيعي,هيدروجين',
                'engines.*.engine_number' => 'nullable|string|max:255',
                'engines.*.manufacturing_country' => 'nullable|string|max:255',
                'engines.*.horsepower' => 'nullable|numeric|min:0',
                'engines.*.installation_date' => 'nullable|date',
                'engines.*.cylinders_count' => 'nullable|integer|min:0',
                'engines.*.installation_type' => 'nullable|in:داخلي,خارجي',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي
            $lastDocument = MarineStructureInsuranceDocument::orderBy('id', 'desc')->first();
            if ($lastDocument && preg_match('/MLMAR(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            $insuranceNumber = 'MLMAR' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // حساب نهاية التأمين إذا لم يتم تحديدها
            $endDate = $validated['end_date'] ?? null;
            if (!$endDate && isset($validated['duration']) && isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration'];
                
                if ($duration === 'سنتين (730 يوم)') {
                    $endDate = $startDate->copy()->addDays(730)->format('Y-m-d');
                } else {
                    $endDate = $startDate->copy()->addDays(365)->format('Y-m-d');
                }
            }

            // تحديد نوع الترخيص بناءً على نوع الهيكل
            $licenseType = $validated['license_type'] ?? null;
            if (!$licenseType) {
                switch ($validated['structure_type']) {
                    case 'القوارب الشخصية والدراجات':
                        $licenseType = 'خاص';
                        break;
                    case 'الآلات والرافعات البحرية':
                        $licenseType = 'صناعي';
                        break;
                    case 'قوارب الصيد':
                        $licenseType = 'تجاري';
                        break;
                }
            }

            // حساب القسط المقرر
            $basePremium = 0;
            if ($validated['duration'] === 'سنة (365 يوم)') {
                if ($validated['structure_type'] === 'القوارب الشخصية والدراجات') {
                    $basePremium = 100.010;
                } else {
                    $basePremium = 150.015;
                }
            } else { // سنتين
                if ($validated['structure_type'] === 'القوارب الشخصية والدراجات') {
                    $basePremium = 200.020; // 100.010 * 2
                } else {
                    $basePremium = 300.030; // 150.015 * 2
                }
            }

            // إضافة 10 لكل راكب
            $passengerCount = $validated['passenger_count'] ?? 0;
            $premium = $basePremium + ($passengerCount * 10);

            // حساب الإجمالي
            $tax = 1.000;
            $stamp = 0.500;
            $issueFees = 2.000;
            $supervisionFees = 0.500;
            $total = $premium + $tax + $stamp + $issueFees + $supervisionFees;

            // الحصول على branch_agent_id من المستخدم الحالي
            $branchAgentId = null;
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            
            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $isAdmin = $user->is_admin ?? false;
                        
                        if (!$isAdmin) {
                            // إذا لم يكن admin، احصل على branch_agent_id من المستخدم
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            $document = MarineStructureInsuranceDocument::create([
                'insurance_number' => $insuranceNumber,
                'issue_date' => Carbon::now(),
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'duration' => $validated['duration'],
                'structure_type' => $validated['structure_type'],
                'license_type' => $licenseType,
                'license_purpose' => $validated['license_purpose'] ?? null,
                'vessel_name' => $validated['vessel_name'] ?? null,
                'registration_code' => $validated['registration_code'] ?? null,
                'registration_date' => $validated['registration_date'] ?? null,
                'port' => $validated['port'] ?? null,
                'registration_authority_id' => $validated['registration_authority_id'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'hull_number' => $validated['hull_number'] ?? null,
                'manufacturing_material' => $validated['manufacturing_material'] ?? null,
                'length' => $validated['length'] ?? null,
                'width' => $validated['width'] ?? null,
                'depth' => $validated['depth'] ?? null,
                'manufacturing_year' => $validated['manufacturing_year'] ?? null,
                'manufacturing_country' => $validated['manufacturing_country'] ?? null,
                'color' => $validated['color'] ?? null,
                'fuel_tank_capacity' => $validated['fuel_tank_capacity'] ?? null,
                'passenger_count' => $passengerCount,
                'load_capacity' => $validated['load_capacity'] ?? null,
                'insured_name' => $validated['insured_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
            ]);

            // إنشاء المحركات
            if (isset($validated['engines']) && is_array($validated['engines'])) {
                foreach ($validated['engines'] as $engineData) {
                    MarineStructureEngine::create([
                        'marine_structure_insurance_document_id' => $document->id,
                        'engine_type' => $engineData['engine_type'],
                        'engine_model' => $engineData['engine_model'] ?? null,
                        'fuel_type' => $engineData['fuel_type'] ?? null,
                        'engine_number' => $engineData['engine_number'] ?? null,
                        'manufacturing_country' => $engineData['manufacturing_country'] ?? null,
                        'horsepower' => $engineData['horsepower'] ?? null,
                        'installation_date' => $engineData['installation_date'] ?? null,
                        'cylinders_count' => $engineData['cylinders_count'] ?? null,
                        'installation_type' => $engineData['installation_type'] ?? null,
                    ]);
                }
            }

            return response()->json($document->load(['registrationAuthority.city', 'engines']), 201);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($document)
    {
        try {
            $document = MarineStructureInsuranceDocument::with(['registrationAuthority.city', 'engines'])->findOrFail($document);
            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@show: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $document)
    {
        try {
            $document = MarineStructureInsuranceDocument::findOrFail($document);
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'required|in:سنة (365 يوم),سنتين (730 يوم)',
                'structure_type' => 'required|in:القوارب الشخصية والدراجات,الآلات والرافعات البحرية,قوارب الصيد',
                'license_type' => 'nullable|in:خاص,صناعي,تجاري',
                'license_purpose' => 'nullable|in:قارب تجاري,قارب حرفي,قارب الترولة,قارب الشباك السينية,قارب الخيوط السنارية,قارب الصيد بالفخ',
                'vessel_name' => 'nullable|string|max:255',
                'registration_code' => 'nullable|string|max:255',
                'registration_date' => 'nullable|date',
                'port' => 'nullable|string|max:255',
                'registration_authority_id' => 'nullable|exists:plates,id',
                'plate_number' => 'nullable|string|max:255',
                'hull_number' => 'nullable|string|max:255',
                'manufacturing_material' => 'nullable|string|max:255',
                'length' => 'nullable|numeric|min:0',
                'width' => 'nullable|numeric|min:0',
                'depth' => 'nullable|numeric|min:0',
                'manufacturing_year' => 'nullable|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'color' => 'nullable|string|max:255',
                'fuel_tank_capacity' => 'nullable|numeric|min:0',
                'passenger_count' => 'nullable|integer|min:0',
                'load_capacity' => 'nullable|numeric|min:0',
                'insured_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'license_number' => 'nullable|string|max:255',
                'engines' => 'nullable|array',
                'engines.*.engine_type' => 'required|in:main,auxiliary',
                'engines.*.engine_model' => 'nullable|string|max:255',
                'engines.*.fuel_type' => 'nullable|in:بنزين Gasoline,ديزل Diesel,كهرباء,غاز طبيعي,هيدروجين',
                'engines.*.engine_number' => 'nullable|string|max:255',
                'engines.*.manufacturing_country' => 'nullable|string|max:255',
                'engines.*.horsepower' => 'nullable|numeric|min:0',
                'engines.*.installation_date' => 'nullable|date',
                'engines.*.cylinders_count' => 'nullable|integer|min:0',
                'engines.*.installation_type' => 'nullable|in:داخلي,خارجي',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // تحميل العلاقات للمستند
            $document->load('engines');

            // حساب نهاية التأمين إذا لم يتم تحديدها
            $endDate = $validated['end_date'] ?? null;
            if (!$endDate && isset($validated['duration']) && isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration'];
                
                if ($duration === 'سنتين (730 يوم)') {
                    $endDate = $startDate->copy()->addDays(730)->format('Y-m-d');
                } else {
                    $endDate = $startDate->copy()->addDays(365)->format('Y-m-d');
                }
            }

            // تحديد نوع الترخيص بناءً على نوع الهيكل
            $licenseType = $validated['license_type'] ?? null;
            if (!$licenseType) {
                switch ($validated['structure_type']) {
                    case 'القوارب الشخصية والدراجات':
                        $licenseType = 'خاص';
                        break;
                    case 'الآلات والرافعات البحرية':
                        $licenseType = 'صناعي';
                        break;
                    case 'قوارب الصيد':
                        $licenseType = 'تجاري';
                        break;
                }
            }

            // حساب القسط المقرر
            $basePremium = 0;
            if ($validated['duration'] === 'سنة (365 يوم)') {
                if ($validated['structure_type'] === 'القوارب الشخصية والدراجات') {
                    $basePremium = 100.010;
                } else {
                    $basePremium = 150.015;
                }
            } else { // سنتين
                if ($validated['structure_type'] === 'القوارب الشخصية والدراجات') {
                    $basePremium = 200.020;
                } else {
                    $basePremium = 300.030;
                }
            }

            // إضافة 10 لكل راكب
            $passengerCount = $validated['passenger_count'] ?? 0;
            $premium = $basePremium + ($passengerCount * 10);

            // حساب الإجمالي
            $tax = 1.000;
            $stamp = 0.500;
            $issueFees = 2.000;
            $supervisionFees = 0.500;
            $total = $premium + $tax + $stamp + $issueFees + $supervisionFees;

            // تحديث branch_agent_id فقط إذا كان المستخدم admin أو إذا لم يكن للوثيقة branch_agent_id
            $branchAgentId = $document->branch_agent_id; // الحفاظ على القيمة الحالية
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $isAdmin = $user->is_admin ?? false;
                    if ($isAdmin) {
                        // Admin يمكنه تغيير branch_agent_id من request إذا كان موجوداً
                        $branchAgentId = $request->input('branch_agent_id') ?? $document->branch_agent_id;
                    } else {
                        // إذا لم يكن admin، احصل على branch_agent_id من المستخدم
                        $branchAgent = BranchAgent::where('user_id', $userId)->first();
                        if ($branchAgent) {
                            // إذا لم يكن للوثيقة branch_agent_id، قم بتعيينه
                            if (!$document->branch_agent_id) {
                                $branchAgentId = $branchAgent->id;
                            }
                            // إذا كان للوثيقة branch_agent_id مختلف، لا تغيره (لأن المستخدم ليس admin)
                        }
                    }
                }
            }

            $document->update([
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'duration' => $validated['duration'],
                'structure_type' => $validated['structure_type'],
                'license_type' => $licenseType,
                'license_purpose' => $validated['license_purpose'] ?? null,
                'vessel_name' => $validated['vessel_name'] ?? null,
                'registration_code' => $validated['registration_code'] ?? null,
                'registration_date' => $validated['registration_date'] ?? null,
                'port' => $validated['port'] ?? null,
                'registration_authority_id' => $validated['registration_authority_id'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'hull_number' => $validated['hull_number'] ?? null,
                'manufacturing_material' => $validated['manufacturing_material'] ?? null,
                'length' => $validated['length'] ?? null,
                'width' => $validated['width'] ?? null,
                'depth' => $validated['depth'] ?? null,
                'manufacturing_year' => $validated['manufacturing_year'] ?? null,
                'manufacturing_country' => $validated['manufacturing_country'] ?? null,
                'color' => $validated['color'] ?? null,
                'fuel_tank_capacity' => $validated['fuel_tank_capacity'] ?? null,
                'passenger_count' => $passengerCount,
                'load_capacity' => $validated['load_capacity'] ?? null,
                'insured_name' => $validated['insured_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'license_number' => $validated['license_number'] ?? null,
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
            ]);

            // حذف المحركات الحالية وإعادة إنشائها
            $document->engines()->delete();

            if (isset($validated['engines']) && is_array($validated['engines'])) {
                foreach ($validated['engines'] as $engineData) {
                    MarineStructureEngine::create([
                        'marine_structure_insurance_document_id' => $document->id,
                        'engine_type' => $engineData['engine_type'],
                        'engine_model' => $engineData['engine_model'] ?? null,
                        'fuel_type' => $engineData['fuel_type'] ?? null,
                        'engine_number' => $engineData['engine_number'] ?? null,
                        'manufacturing_country' => $engineData['manufacturing_country'] ?? null,
                        'horsepower' => $engineData['horsepower'] ?? null,
                        'installation_date' => $engineData['installation_date'] ?? null,
                        'cylinders_count' => $engineData['cylinders_count'] ?? null,
                        'installation_type' => $engineData['installation_type'] ?? null,
                    ]);
                }
            }

            return response()->json($document->load(['registrationAuthority.city', 'engines']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($document)
    {
        try {
            $document = MarineStructureInsuranceDocument::findOrFail($document);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print marine structure insurance document.
     */
    public function print($document)
    {
        try {
            $document = MarineStructureInsuranceDocument::with(['registrationAuthority.city:id,name_ar,name_en,order', 'engines', 'branchAgent'])->findOrFail($document);
            
            $mainEngine = $document->engines->where('engine_type', 'main')->first();
            $auxiliaryEngine = $document->engines->where('engine_type', 'auxiliary')->first();
            $mainEngineHorsepower = $mainEngine && $mainEngine->horsepower ? $mainEngine->horsepower : 0;
            $auxiliaryEngineHorsepower = $auxiliaryEngine && $auxiliaryEngine->horsepower ? $auxiliaryEngine->horsepower : 0;
            
            // قائمة الدول للبحث عن الاسم الإنجليزي
            $countries = [
                'مصري' => 'Egyptian', 'سوداني' => 'Sudanese', 'ليبي' => 'Libyan', 'تونسي' => 'Tunisian',
                'جزائري' => 'Algerian', 'مغربي' => 'Moroccan', 'موريتاني' => 'Mauritanian', 'صحراوي' => 'Sahrawi',
                'تشادي' => 'Chadian', 'نيجري' => 'Nigerien', 'مالي' => 'Malian', 'سنغالي' => 'Senegalese',
                'غامبي' => 'Gambian', 'غيني' => 'Guinean', 'غيني-بيساوي' => 'Bissau-Guinean', 'سيراليوني' => 'Sierra Leonean',
                'ليبيري' => 'Liberian', 'إيفواري (ساحل العاج)' => 'Ivorian', 'غاني' => 'Ghanaian', 'توغولي' => 'Togolese',
                'بنيني' => 'Beninese', 'نيجيري' => 'Nigerian', 'كاميروني' => 'Cameroonian', 'كونغولي' => 'Congolese',
                'كونغولي (جمهورية الكونغو الديمقراطية)' => 'Congolese (DRC)', 'أنغولي' => 'Angolan', 'زامبي' => 'Zambian',
                'زيمبابوي' => 'Zimbabwean', 'بوتسواني' => 'Botswanan', 'ناميبي' => 'Namibian', 'ليسوتوي' => 'Basotho',
                'إسواتيني' => 'Swazi', 'مدغشقري' => 'Malagasy', 'موريشي' => 'Mauritian', 'سيشيلي' => 'Seychellois',
                'جزر قمري' => 'Comorian', 'جيبوتي' => 'Djiboutian', 'صومالي' => 'Somali', 'إثيوبي' => 'Ethiopian',
                'إريتري' => 'Eritrean', 'جنوب سوداني' => 'South Sudanese', 'أوغندي' => 'Ugandan', 'كيني' => 'Kenyan',
                'تنزاني' => 'Tanzanian', 'رواندي' => 'Rwandan', 'بوروندي' => 'Burundian', 'ملاوي' => 'Malawian',
                'موزمبيقي' => 'Mozambican', 'سعودي' => 'Saudi', 'كويتي' => 'Kuwaiti', 'قطري' => 'Qatari',
                'بحريني' => 'Bahraini', 'إماراتي' => 'Emirati', 'عماني' => 'Omani', 'يمني' => 'Yemeni',
                'عراقي' => 'Iraqi', 'سوري' => 'Syrian', 'لبناني' => 'Lebanese', 'أردني' => 'Jordanian',
                'فلسطيني' => 'Palestinian', 'تركي' => 'Turkish', 'إيراني' => 'Iranian', 'أفغاني' => 'Afghan',
                'باكستاني' => 'Pakistani', 'هندي' => 'Indian', 'نيبالي' => 'Nepali', 'بنغلاديشي' => 'Bangladeshi',
                'سريلانكي' => 'Sri Lankan', 'بوتاني' => 'Bhutanese', 'مالديفي' => 'Maldivian', 'صيني' => 'Chinese',
                'ياباني' => 'Japanese', 'كوري جنوبي' => 'South Korean', 'كوري شمالي' => 'North Korean', 'منغولي' => 'Mongolian',
                'كازاخستاني' => 'Kazakh', 'أوزبكي' => 'Uzbek', 'تركماني' => 'Turkmen', 'طاجيكي' => 'Tajik',
                'قيرغيزي' => 'Kyrgyz', 'ميانماري' => 'Burmese', 'تايلاندي' => 'Thai', 'كامبودي' => 'Cambodian',
                'فيتنامي' => 'Vietnamese', 'لاوسي' => 'Laotian', 'ماليزاي' => 'Malaysian', 'سنغافوري' => 'Singaporean',
                'إندونيسي' => 'Indonesian', 'فلبيني' => 'Filipino', 'تيموري' => 'Timorese', 'جورجي' => 'Georgian',
                'أرميني' => 'Armenian', 'أذربيجاني' => 'Azerbaijani', 'قبرصي' => 'Cypriot', 'بريطاني' => 'British',
                'إنجليزي' => 'English', 'إسكتلندي' => 'Scottish', 'ويلزي' => 'Welsh', 'إيرلندي' => 'Irish',
                'فرنسي' => 'French', 'ألماني' => 'German', 'إيطالي' => 'Italian', 'إسباني' => 'Spanish',
                'برتغالي' => 'Portuguese', 'هولندي' => 'Dutch', 'بلجيكي' => 'Belgian', 'لوكسمبورغي' => 'Luxembourger',
                'نمساوي' => 'Austrian', 'سويسري' => 'Swiss', 'دنماركي' => 'Danish', 'سويدي' => 'Swedish',
                'نرويجي' => 'Norwegian', 'فنلندي' => 'Finnish', 'آيسلندي' => 'Icelandic', 'بولندي' => 'Polish',
                'تشيكي' => 'Czech', 'سلوفاكي' => 'Slovak', 'هنغاري' => 'Hungarian', 'روماني' => 'Romanian',
                'بلغاري' => 'Bulgarian', 'صربي' => 'Serbian', 'كرواتي' => 'Croatian', 'بوسني' => 'Bosnian',
                'سلوفيني' => 'Slovenian', 'مقدوني' => 'Macedonian', 'ألباني' => 'Albanian', 'يوناني' => 'Greek',
                'مالطي' => 'Maltese', 'ليتواني' => 'Lithuanian', 'لاتفي' => 'Latvian', 'إستوني' => 'Estonian',
                'أوكراني' => 'Ukrainian', 'روسي' => 'Russian', 'بيلاروسي' => 'Belarusian', 'مولدوفي' => 'Moldovan',
                'أمريكي' => 'American', 'كندي' => 'Canadian', 'مكسيكي' => 'Mexican', 'غواتيمالي' => 'Guatemalan',
                'هندوراسي' => 'Honduran', 'سلفادوري' => 'Salvadoran', 'نيكاراغوي' => 'Nicaraguan', 'كوستاريكي' => 'Costa Rican',
                'بانامي' => 'Panamanian', 'كوبي' => 'Cuban', 'دومينيكاني' => 'Dominican', 'هايتي' => 'Haitian',
                'جامايكي' => 'Jamaican', 'باهامي' => 'Bahamian', 'بربادوسي' => 'Barbadian', 'ترينيدادي' => 'Trinidadian',
                'أنتيغوي' => 'Antiguan', 'سانت لوسي' => 'Saint Lucian', 'غرينادي' => 'Grenadian', 'برازيلي' => 'Brazilian',
                'أرجنتيني' => 'Argentine', 'أوروغواياني' => 'Uruguayan', 'باراغوايي' => 'Paraguayan', 'تشيلي' => 'Chilean',
                'بوليفي' => 'Bolivian', 'بيروفي' => 'Peruvian', 'إكوادوري' => 'Ecuadorian', 'سورينامي' => 'Surinamese',
                'غوياني' => 'Guyanese', 'أسترالي' => 'Australian', 'نيوزيلندي' => 'New Zealander', 'بابواني' => 'Papuan',
                'فيجياني' => 'Fijian', 'سامواني' => 'Samoan', 'تونغاني' => 'Tongan', 'فانواتي' => 'Vanuatuan',
                'كيريباتي' => 'Kiribati', 'ميكرونيزي' => 'Micronesian', 'مارشالي' => 'Marshallese', 'ناورووي' => 'Nauruan',
                'بالاوي' => 'Palauan', 'توفالي' => 'Tuvaluan',
            ];
            
            // تحضير بيانات الوكالة
            $agencyData = [
                'agency_name' => 'المدار الليبي للتأمين',
                'code' => 'ML0001',
                'agent_name' => 'محمد علي',
            ];
            
            if ($document->branchAgent) {
                $agencyData['agency_name'] = $document->branchAgent->agency_name ?? 'المدار الليبي للتأمين';
                $agencyData['code'] = $document->branchAgent->code ?? 'ML0001';
                $agencyData['agent_name'] = $document->branchAgent->agent_name ?? 'محمد علي';
            }
            
            $getCountryDisplay = function($arabicName) use ($countries) {
                return isset($countries[$arabicName]) ? $arabicName . ' ' . $countries[$arabicName] : $arabicName;
            };
            
            $formatRegistrationAuthority = function($document) {
                // استخدام registrationAuthority مباشرة
                $registrationAuthority = $document->registrationAuthority;
                
                // إعادة تحميل العلاقة city إذا لم تكن محملة
                if ($registrationAuthority && !$registrationAuthority->relationLoaded('city')) {
                    $registrationAuthority->load('city:id,name_ar,name_en,order');
                }
                
                $portValue = trim($document->port ?? '');
                $hasPort = !empty($portValue);
                
                // التحقق من وجود city في registrationAuthority
                if ($registrationAuthority && $registrationAuthority->city) {
                    $city = $registrationAuthority->city;
                    // التأكد من تحميل name_ar و name_en
                    if (!isset($city->name_ar) || !isset($city->name_en)) {
                        $city = \App\Models\City::select('id', 'name_ar', 'name_en', 'order')->find($city->id);
                    }
                    if ($city && isset($city->name_ar)) {
                        $result = $city->name_ar;
                        if ($city->name_en) {
                            $result .= ' ' . $city->name_en;
                        }
                        return $result;
                    }
                } elseif ($hasPort) {
                    return $portValue;
                } elseif ($registrationAuthority) {
                    return $registrationAuthority->plate_number ?? '-';
                }
                
                return '-';
            };
            
            $formatPlateNumber = function($document) {
                $registrationAuthority = $document->registrationAuthority;
                if ($registrationAuthority && $registrationAuthority->city && isset($registrationAuthority->city->order)) {
                    return $registrationAuthority->city->order . '-' . ($document->plate_number ?? $registrationAuthority->plate_number);
                }
                return $document->plate_number ?? ($registrationAuthority ? $registrationAuthority->plate_number : '-');
            };
            
            // تحضير البيانات للطباعة
            $printData = [
                'issue_date' => Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'insurance_number' => $document->insurance_number,
                'start_date' => Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($document->end_date)->format('d/m/Y'),
                'duration' => $document->duration === 'سنة (365 يوم)' ? '365 يوم' : ($document->duration === 'سنتين (730 يوم)' ? '730 يوم' : $document->duration),
                'insured_name' => $document->insured_name ?? '-',
                'phone' => $document->phone ?? '-',
                'license_number' => $document->license_number ?? '-',
                'vessel_name' => $document->vessel_name ?? '-',
                'plate_number' => $formatPlateNumber($document),
                'registration_authority' => $formatRegistrationAuthority($document),
                'hull_number' => $document->hull_number ?? '-',
                'passenger_count' => $document->passenger_count ?? 0,
                'load_capacity' => $document->load_capacity ?? 0,
                'license_purpose' => $document->license_purpose ?? '-',
                'manufacturing_country' => $document->manufacturing_country ? $getCountryDisplay($document->manufacturing_country) : '-',
                'color' => $document->color ?? '-',
                'port' => $document->port ?? '-',
                'manufacturing_material' => $document->manufacturing_material ?? '-',
                'manufacturing_year' => $document->manufacturing_year ?? '-',
                'size' => ($document->length ?? 0) . ' × ' . ($document->width ?? 0) . ' × ' . ($document->depth ?? 0),
                'fuel_tank_capacity' => $document->fuel_tank_capacity ?? 0,
                'main_engine_horsepower' => $mainEngineHorsepower,
                'auxiliary_engine_horsepower' => $auxiliaryEngineHorsepower,
                'engine_horsepower_display' => $mainEngineHorsepower . ' / ' . $auxiliaryEngineHorsepower,
                'premium' => number_format($document->premium, 3, '.', ''),
                'tax' => number_format($document->tax, 3, '.', ''),
                'stamp' => number_format($document->stamp, 3, '.', ''),
                'issue_fees' => number_format($document->issue_fees, 3, '.', ''),
                'supervision_fees' => number_format($document->supervision_fees, 3, '.', ''),
                'total' => number_format($document->total, 3, '.', ''),
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'prepared_at' => Carbon::now()->format('d/m/y H:i:s'),
                'qr_data' => [
                    'insurance_number' => $document->insurance_number,
                    'issue_date' => Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $document->insured_name ?? '',
                    'total' => $document->total
                ]
            ];
            
            return view('marine-structure-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in MarineStructureInsuranceDocumentController@print: ' . $e->getMessage());
            abort(404, 'الوثيقة غير موجودة');
        }
    }
    
    private function numberToArabicWords($number)
    {
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        
        // فصل الجزء الصحيح والجزء العشري
        $parts = explode('.', (string)$number);
        $integerPart = (int)($parts[0] ?? 0);
        $decimalPart = isset($parts[1]) ? (int)($parts[1]) : 0;
        
        // تحويل الجزء الصحيح
        $words = '';
        
        if ($integerPart == 0 && $decimalPart == 0) {
            return 'صفر دينار';
        }
        
        if ($integerPart > 0) {
            $num = $integerPart;
            
            // الآلاف
            if ($num >= 1000) {
                $thousands = (int)($num / 1000);
                if ($thousands == 1) {
                    $words .= 'ألف ';
                } elseif ($thousands == 2) {
                    $words .= 'ألفان ';
                } elseif ($thousands >= 3 && $thousands <= 10) {
                    $words .= $ones[$thousands] . ' آلاف ';
                } else {
                    $words .= number_format($thousands) . ' ألف ';
                }
                $num = $num % 1000;
            }
            
            // المئات
            if ($num >= 100) {
                $hundred = (int)($num / 100);
                $words .= $hundreds[$hundred] . ' ';
                $num = $num % 100;
            }
            
            // العشرات والآحاد
            if ($num >= 20) {
                $ten = (int)($num / 10);
                $one = $num % 10;
                if ($one > 0) {
                    $words .= $ones[$one] . ' و' . $tens[$ten];
                } else {
                    $words .= $tens[$ten];
                }
            } elseif ($num >= 10) {
                $words .= $teens[$num - 10];
            } elseif ($num > 0) {
                $words .= $ones[$num];
            }
            
            $words .= ' دينار';
        }
        
        // تحويل الجزء العشري
        if ($decimalPart > 0) {
            if ($integerPart > 0) {
                $words .= ' و';
            }
            $words .= $decimalPart . ' درهم';
        }
        
        return trim($words);
    }
}
