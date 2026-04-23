<?php

namespace App\Http\Controllers;

use App\Models\InsuranceDocument;
use App\Models\InsuranceOwnershipTransfer;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class InsuranceDocumentController extends Controller
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
            $query = InsuranceDocument::with(['plate.city', 'vehicleType', 'branchAgent']);
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
                      ->orWhere('insurance_type', 'like', "%{$search}%");
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
                $transferCount = InsuranceOwnershipTransfer::where('insurance_document_id', $document->id)->count();
                $document->ownership_transfer_count = $transferCount;
                $document->has_ownership_transfer = $transferCount > 0;
                
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
            Log::error('Error in InsuranceDocumentController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
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
                'insurance_type' => 'required|in:تأمين إجباري سيارات,تأمين سيارة جمرك,تأمين طرف ثالث سيارات,تأمين سيارات أجنبية',
                'plate_id' => 'nullable|exists:plates,id',
                'port' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'nullable|string|max:255',
                'chassis_number' => 'nullable|string|max:255',
                'plate_number_manual' => 'nullable|string|max:255',
                'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
                'color' => 'nullable|string|max:255',
                'year' => 'nullable|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'fuel_type' => 'nullable|in:بنزين/Gasoline,ديزل/Diesel,كهرباء/Electric,غاز طبيعي/CNG,هيدروجين/Hydrogen',
                'license_purpose' => 'nullable|in:خاصة/Private,عامة/Public,نقل/Transport,زراعي/Agricultural,صناعي/Industrial',
                'engine_power' => 'nullable|string|max:255',
                'authorized_passengers' => 'nullable|integer|min:0|max:100',
                'load_capacity' => 'nullable|numeric|min:0|max:100',
                'insured_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'driving_license_number' => 'nullable|string|max:255',
                'premium' => 'required|numeric|min:0|max:999999',
                'third_party_purpose' => 'nullable|string|max:255',
                'foreign_car_country' => 'nullable|string|max:255',
                'foreign_car_purpose' => 'nullable|string|max:255',
                'print_type' => 'nullable|in:A5,A4',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي
            $lastDocument = InsuranceDocument::orderBy('id', 'desc')->first();
            if ($lastDocument && preg_match('/BKMCI(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            $insuranceNumber = 'BKMCI' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // حساب نهاية التأمين إذا تم تحديد المدة
            $endDate = $validated['end_date'] ?? null;
            if (!$endDate && isset($validated['duration']) && isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration'];
                
                // تأمين جمرك أو سيارات أجنبية - حساب بالأيام
                if ($validated['insurance_type'] === 'تأمين سيارة جمرك' || $validated['insurance_type'] === 'تأمين سيارات أجنبية') {
                    $days = 0;
                    switch ($duration) {
                        case 'أسبوعين (15 يوم)':
                            $days = 15;
                            break;
                        case 'شهر (30 يوم)':
                            $days = 30;
                            break;
                        case 'شهرين (60 يوم)':
                            $days = 60;
                            break;
                        case 'ثلاثة أشهر (90 يوم)':
                            $days = 90;
                            break;
                        case 'سنة (365 يوم)':
                            $days = 365;
                            break;
                        case 'سنتين (730 يوم)':
                            $days = 730;
                            break;
                    }
                    $endDate = $startDate->copy()->addDays($days)->format('Y-m-d');
                } else {
                    // تأمين عادي - حساب بالسنوات
                    if ($duration === 'سنتين (730 يوم)' || $duration === 'سنتين') {
                        $endDate = $startDate->copy()->addYears(2)->format('Y-m-d');
                    } else {
                        // سنة (365 يوم) أو سنة (للتوافق مع البيانات القديمة)
                        $endDate = $startDate->copy()->addYear()->format('Y-m-d');
                    }
                }
            }

            // حساب الإجمالي
            $premium = $validated['premium'] ?? 0;
            $tax = 1.000;
            $stamp = 0.500;
            $issueFees = 2.000;
            $supervisionFees = 0.500;
            $total = $premium + $tax + $stamp + $issueFees + $supervisionFees;

            // الحصول على branch_agent_id من المستخدم الحالي
            $branchAgentId = null;
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            Log::info('Creating insurance document - User ID from request:', [
                'header_X-User-Id' => $request->header('X-User-Id'),
                'input_user_id' => $request->input('user_id'),
                'userId' => $userId,
            ]);
            
            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $isAdmin = $user->is_admin ?? false;
                        Log::info('User found:', [
                            'user_id' => $userId,
                            'is_admin' => $isAdmin,
                        ]);
                        
                        if (!$isAdmin) {
                            // إذا لم يكن admin، احصل على branch_agent_id من المستخدم
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                                Log::info('Branch agent found:', [
                                    'branch_agent_id' => $branchAgentId,
                                    'agency_name' => $branchAgent->agency_name,
                                ]);
                            } else {
                                Log::warning('No branch agent found for user:', ['user_id' => $userId]);
                            }
                        }
                    } else {
                        Log::warning('User not found:', ['user_id' => $userId]);
                    }
                }
            } else {
                Log::warning('No user ID provided in request');
            }
            
            Log::info('Final branch_agent_id to save:', ['branch_agent_id' => $branchAgentId]);

            $document = InsuranceDocument::create([
                'insurance_type' => $validated['insurance_type'],
                'insurance_number' => $insuranceNumber,
                'issue_date' => now(),
                'plate_id' => $validated['plate_id'] ?? null,
                'port' => $validated['port'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'duration' => $validated['duration'] ?? 'سنة',
                'third_party_purpose' => $validated['third_party_purpose'] ?? null,
                'foreign_car_country' => $validated['foreign_car_country'] ?? null,
                'foreign_car_purpose' => $validated['foreign_car_purpose'] ?? null,
                'chassis_number' => $validated['chassis_number'] ?? null,
                'plate_number_manual' => $validated['plate_number_manual'] ?? null,
                'vehicle_type_id' => $validated['vehicle_type_id'] ?? null,
                'color' => $validated['color'] ?? null,
                'year' => $validated['year'] ?? null,
                'manufacturing_country' => $validated['manufacturing_country'] ?? null,
                'fuel_type' => $validated['fuel_type'] ?? null,
                'license_purpose' => $validated['license_purpose'] ?? null,
                'engine_power' => $validated['engine_power'] ?? null,
                'authorized_passengers' => $validated['authorized_passengers'] ?? null,
                'load_capacity' => $validated['load_capacity'] ?? null,
                'insured_name' => $validated['insured_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'driving_license_number' => $validated['driving_license_number'] ?? null,
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
                'print_type' => $validated['print_type'] ?? 'A4',
                'branch_agent_id' => $branchAgentId,
            ]);

            return response()->json($document->load(['plate.city', 'vehicleType']), 201);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $document = InsuranceDocument::with(['plate.city', 'vehicleType'])->findOrFail($id);
            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@show: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'insurance_type' => 'required|in:تأمين إجباري سيارات,تأمين سيارة جمرك,تأمين طرف ثالث سيارات,تأمين سيارات أجنبية',
                'plate_id' => 'nullable|exists:plates,id',
                'port' => 'nullable|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'nullable|string|max:255',
                'chassis_number' => 'nullable|string|max:255',
                'plate_number_manual' => 'nullable|string|max:255',
                'vehicle_type_id' => 'nullable|exists:vehicle_types,id',
                'color' => 'nullable|string|max:255',
                'year' => 'nullable|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'fuel_type' => 'nullable|in:بنزين/Gasoline,ديزل/Diesel,كهرباء/Electric,غاز طبيعي/CNG,هيدروجين/Hydrogen',
                'license_purpose' => 'nullable|in:خاصة/Private,عامة/Public,نقل/Transport,زراعي/Agricultural,صناعي/Industrial',
                'engine_power' => 'nullable|string|max:255',
                'authorized_passengers' => 'nullable|integer|min:0|max:100',
                'load_capacity' => 'nullable|numeric|min:0|max:100',
                'insured_name' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:255',
                'driving_license_number' => 'nullable|string|max:255',
                'premium' => 'required|numeric|min:0|max:999999',
                'third_party_purpose' => 'nullable|string|max:255',
                'foreign_car_country' => 'nullable|string|max:255',
                'foreign_car_purpose' => 'nullable|string|max:255',
                'print_type' => 'nullable|in:A5,A4',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $document = InsuranceDocument::findOrFail($id);

            // حساب نهاية التأمين إذا تم تحديد المدة
            $endDate = $validated['end_date'] ?? null;
            if (!$endDate && isset($validated['duration']) && isset($validated['start_date'])) {
                $startDate = Carbon::parse($validated['start_date']);
                $duration = $validated['duration'];
                
                // تأمين جمرك أو سيارات أجنبية - حساب بالأيام
                if ($validated['insurance_type'] === 'تأمين سيارة جمرك' || $validated['insurance_type'] === 'تأمين سيارات أجنبية') {
                    $days = 0;
                    switch ($duration) {
                        case 'أسبوعين (15 يوم)':
                            $days = 15;
                            break;
                        case 'شهر (30 يوم)':
                            $days = 30;
                            break;
                        case 'شهرين (60 يوم)':
                            $days = 60;
                            break;
                        case 'ثلاثة أشهر (90 يوم)':
                            $days = 90;
                            break;
                        case 'سنة (365 يوم)':
                            $days = 365;
                            break;
                        case 'سنتين (730 يوم)':
                            $days = 730;
                            break;
                    }
                    $endDate = $startDate->copy()->addDays($days)->format('Y-m-d');
                } else {
                    // تأمين عادي - حساب بالسنوات
                    if ($duration === 'سنتين (730 يوم)' || $duration === 'سنتين') {
                        $endDate = $startDate->copy()->addYears(2)->format('Y-m-d');
                    } else {
                        // سنة (365 يوم) أو سنة (للتوافق مع البيانات القديمة)
                        $endDate = $startDate->copy()->addYear()->format('Y-m-d');
                    }
                }
            }

            // حساب الإجمالي
            $premium = $validated['premium'] ?? 0;
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
                'insurance_type' => $validated['insurance_type'],
                'plate_id' => $validated['plate_id'] ?? null,
                'port' => $validated['port'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $endDate,
                'duration' => $validated['duration'] ?? 'سنة',
                'third_party_purpose' => $validated['third_party_purpose'] ?? null,
                'foreign_car_country' => $validated['foreign_car_country'] ?? null,
                'foreign_car_purpose' => $validated['foreign_car_purpose'] ?? null,
                'chassis_number' => $validated['chassis_number'] ?? null,
                'branch_agent_id' => $branchAgentId,
                'plate_number_manual' => $validated['plate_number_manual'] ?? null,
                'vehicle_type_id' => $validated['vehicle_type_id'] ?? null,
                'color' => $validated['color'] ?? null,
                'year' => $validated['year'] ?? null,
                'manufacturing_country' => $validated['manufacturing_country'] ?? null,
                'fuel_type' => $validated['fuel_type'] ?? null,
                'license_purpose' => $validated['license_purpose'] ?? null,
                'engine_power' => $validated['engine_power'] ?? null,
                'authorized_passengers' => $validated['authorized_passengers'] ?? null,
                'load_capacity' => $validated['load_capacity'] ?? null,
                'insured_name' => $validated['insured_name'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'driving_license_number' => $validated['driving_license_number'] ?? null,
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
                'print_type' => $validated['print_type'] ?? 'A4',
            ]);

            return response()->json($document->load(['plate.city', 'vehicleType']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Transfer ownership of insurance document
     */
    public function transferOwnership(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'plate_id' => 'nullable|exists:plates,id',
                'plate_number_manual' => 'nullable|string|max:255',
                'insured_name' => 'required|string|max:255',
                'phone' => 'nullable|string|max:255',
                'driving_license_number' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $document = InsuranceDocument::with(['plate.city', 'vehicleType'])->findOrFail($id);
            
            // التحقق من نوع التأمين
            $isMandatoryInsurance = $document->insurance_type === 'تأمين إجباري سيارات';
            $isThirdPartyInsurance = $document->insurance_type === 'تأمين طرف ثالث سيارات';
            
            // التحقق من أن plate_id مطلوب للتأمين الإجباري وطرف ثالث
            if (($isMandatoryInsurance || $isThirdPartyInsurance) && !isset($validated['plate_id'])) {
                return response()->json([
                    'message' => 'الجهة المقيد بها مطلوبة',
                    'errors' => ['plate_id' => ['الجهة المقيد بها مطلوبة']]
                ], 422);
            }

            // حفظ البيانات السابقة قبل التحديث
            $previousData = [
                'previous_plate_id' => $document->plate_id,
                'previous_plate_number_manual' => $document->plate_number_manual,
                'previous_insured_name' => $document->insured_name,
                'previous_phone' => $document->phone,
                'previous_driving_license_number' => $document->driving_license_number,
            ];

            // تحديث البيانات القابلة للتعديل فقط
            $document->update([
                'plate_id' => ($isMandatoryInsurance || $isThirdPartyInsurance) ? ($validated['plate_id'] ?? null) : $document->plate_id,
                'plate_number_manual' => $validated['plate_number_manual'] ?? $document->plate_number_manual,
                'insured_name' => $validated['insured_name'],
                'phone' => $validated['phone'] ?? null,
                'driving_license_number' => $validated['driving_license_number'] ?? null,
            ]);

            // حفظ السجل التاريخي
            InsuranceOwnershipTransfer::create([
                'insurance_document_id' => $document->id,
                'previous_plate_id' => $previousData['previous_plate_id'],
                'previous_plate_number_manual' => $previousData['previous_plate_number_manual'],
                'previous_insured_name' => $previousData['previous_insured_name'],
                'previous_phone' => $previousData['previous_phone'],
                'previous_driving_license_number' => $previousData['previous_driving_license_number'],
                'new_plate_id' => ($isMandatoryInsurance || $isThirdPartyInsurance) ? ($validated['plate_id'] ?? null) : $document->plate_id,
                'new_plate_number_manual' => $validated['plate_number_manual'] ?? $document->plate_number_manual,
                'new_insured_name' => $validated['insured_name'],
                'new_phone' => $validated['phone'] ?? null,
                'new_driving_license_number' => $validated['driving_license_number'] ?? null,
                'transferred_at' => Carbon::now(),
            ]);

            return response()->json($document->load(['plate.city', 'vehicleType']));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@transferOwnership: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء نقل الملكية',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Get ownership transfer history for an insurance document
     */
    public function getOwnershipTransferHistory(string $id)
    {
        try {
            $document = InsuranceDocument::findOrFail($id);
            
            $transfers = InsuranceOwnershipTransfer::where('insurance_document_id', $id)
                ->with(['previousPlate.city', 'newPlate.city'])
                ->orderBy('transferred_at', 'desc')
                ->get();
            
            return response()->json($transfers);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@getOwnershipTransferHistory: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب تاريخ نقل الملكية',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $document = InsuranceDocument::findOrFail($id);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print insurance document
     */
    public function print(string $id)
    {
        try {
            $document = InsuranceDocument::with(['plate.city', 'vehicleType', 'branchAgent'])->findOrFail($id);
            
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
            
            // تحضير البيانات للطباعة لتسريع العملية
            $printData = [
                'insurance_number' => $document->insurance_number,
                'insurance_type' => $document->insurance_type ?? 'تأمين إجباري سيارات',
                'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'start_date' => \Carbon\Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => $document->end_date ? \Carbon\Carbon::parse($document->end_date)->format('d/m/Y') : '-',
                'duration' => $this->formatDuration($document),
                'plate_number' => $this->formatPlateNumber($document),
                'city_name' => $this->formatCityName($document),
                'port_value' => trim($document->port ?? ''),
                'is_customs_insurance' => ($document->insurance_type === 'تأمين سيارة جمرك'),
                'load_capacity' => $this->formatLoadCapacity($document->load_capacity),
                'vehicle_type' => $document->vehicleType ? ($document->vehicleType->brand . ($document->vehicleType->category ? ' / ' . $document->vehicleType->category : '')) : '-',
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'qr_data' => [
                    'insurance_number' => $document->insurance_number,
                    'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $document->insured_name ?? '',
                    'total' => $document->total
                ]
            ];
            
            return view('insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in InsuranceDocumentController@print: ' . $e->getMessage());
            abort(404, 'الوثيقة غير موجودة');
        }
    }
    
    private function formatDuration($document)
    {
        if (!$document->duration) {
            return '-';
        }
        
        // في حالة تأمين إجباري سيارات، نعرض عدد الأيام فقط
        if ($document->insurance_type === 'تأمين إجباري سيارات') {
            if ($document->duration === 'سنة' || $document->duration === 'سنة (365 يوم)') {
                return '365 يوم';
            } elseif ($document->duration === 'سنتين' || $document->duration === 'سنتين (730 يوم)') {
                return '730 يوم';
            }
        }
        
        // في حالة تأمين جمرك أو سيارات أجنبية، نحسب الأيام
        if (str_contains($document->duration, 'يوم')) {
            $days = \Carbon\Carbon::parse($document->start_date)->diffInDays(\Carbon\Carbon::parse($document->end_date));
            return $days . ' يوم';
        }
        
        return $document->duration;
    }
    
    private function formatPlateNumber($document)
    {
        $isCustomsInsurance = ($document->insurance_type === 'تأمين سيارة جمرك');
        $plateNumber = $document->plate_number_manual ?? ($document->plate ? $document->plate->plate_number : null);
        $cityOrder = $document->plate && $document->plate->city && isset($document->plate->city->order) ? $document->plate->city->order : null;
        
        // في حالة تأمين جمرك
        if ($isCustomsInsurance && $document->port) {
            // استخراج رقم الميناء من اسم الميناء (مثل "ميناء مصراته" -> "3")
            $portNumber = $this->getPortNumber($document->port);
            
            // إذا كان هناك رقم لوحة ورقم ميناء، نعرضهما معاً
            if ($plateNumber && $portNumber) {
                return $portNumber . '-' . $plateNumber;
            } elseif ($plateNumber) {
                // إذا كان هناك رقم لوحة فقط، نعرضه مع اسم الميناء
                return trim($document->port) . ' - ' . $plateNumber;
            } elseif ($portNumber) {
                // إذا كان هناك رقم ميناء فقط
                return $portNumber;
            } else {
                // إذا كان هناك اسم الميناء فقط
                return trim($document->port);
            }
        }
        
        // في الحالات الأخرى
        if ($plateNumber && $cityOrder) {
            return $cityOrder . '-' . $plateNumber;
        } elseif ($plateNumber) {
            return $plateNumber;
        } elseif ($document->port) {
            return 'جمرك';
        }
        
        return '-';
    }
    
    private function getPortNumber($portName)
    {
        // قائمة الموانئ وأرقامها
        $ports = [
            'ميناء مصراته' => '3',
            'ميناء طرابلس' => '5',
            'ميناء الخمس' => '6',
            'ميناء بنغازي' => '8',
        ];
        
        // البحث عن رقم الميناء
        foreach ($ports as $port => $number) {
            if (str_contains($portName, $port) || str_contains($port, $portName)) {
                return $number;
            }
        }
        
        // إذا لم يتم العثور على رقم، حاول استخراج رقم من النص
        if (preg_match('/\d+/', $portName, $matches)) {
            return $matches[0];
        }
        
        return null;
    }
    
    private function formatCityName($document)
    {
        $isCustomsInsurance = ($document->insurance_type === 'تأمين سيارة جمرك');
        $portValue = trim($document->port ?? '');
        $hasPort = !empty($portValue);
        $hasPlateCity = ($document->plate && $document->plate->city);
        
        if ($isCustomsInsurance) {
            return $hasPort ? $portValue : '-';
        } elseif ($hasPlateCity) {
            $city = $document->plate->city;
            return $city->name_ar . ($city->name_en ? ' ' . $city->name_en : '');
        } elseif ($hasPort) {
            return $portValue;
        }
        
        return '-';
    }
    
    private function formatLoadCapacity($loadCapacity)
    {
        if (!$loadCapacity) {
            return '0';
        }
        
        $loadCapacity = floatval($loadCapacity);
        $isInteger = ($loadCapacity == intval($loadCapacity));
        
        return $isInteger ? intval($loadCapacity) : number_format($loadCapacity, 2, '.', '');
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
                if ($hundred <= 9 && isset($hundreds[$hundred])) {
                    $words .= $hundreds[$hundred] . ' ';
                } else {
                    $words .= number_format($hundred) . ' مائة ';
                }
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
    
    private function convertDecimalToWords($decimal, $length)
    {
        $ones = ['', 'واحد', 'اثنان', 'ثلاثة', 'أربعة', 'خمسة', 'ستة', 'سبعة', 'ثمانية', 'تسعة'];
        $teens = ['عشرة', 'أحد عشر', 'اثنا عشر', 'ثلاثة عشر', 'أربعة عشر', 'خمسة عشر', 'ستة عشر', 'سبعة عشر', 'ثمانية عشر', 'تسعة عشر'];
        $tens = ['', '', 'عشرون', 'ثلاثون', 'أربعون', 'خمسون', 'ستون', 'سبعون', 'ثمانون', 'تسعون'];
        $hundreds = ['', 'مائة', 'مائتان', 'ثلاثمائة', 'أربعمائة', 'خمسمائة', 'ستمائة', 'سبعمائة', 'ثمانمائة', 'تسعمائة'];
        
        $words = '';
        $num = (int)$decimal;
        
        if ($num == 0) {
            return '';
        }
        
        // المئات
        if ($num >= 100) {
            $hundred = (int)($num / 100);
            if ($hundred <= 9 && isset($hundreds[$hundred])) {
                $words .= $hundreds[$hundred] . ' ';
            } else {
                $words .= number_format($hundred) . ' مائة ';
            }
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
        
        // إضافة المقام (درهم للكسور)
        if ($length == 3) {
            $words .= ' درهم';
        } elseif ($length == 2) {
            $words .= ' درهم';
        } elseif ($length == 1) {
            $words .= ' درهم';
        }
        
        return trim($words);
    }
}
