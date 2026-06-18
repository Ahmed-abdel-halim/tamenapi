<?php

namespace App\Http\Controllers;

use App\Models\InsuranceDocument;
use App\Models\InsuranceOwnershipTransfer;
use App\Models\BranchAgent;
use App\Models\User;
use App\Services\EidcApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
                $userId = is_numeric($userId) ? (int) $userId : null;
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
            
            $hasFilterOrSearch = $request->filled('search') || 
                                 $request->filled('year') || 
                                 $request->filled('month') || 
                                 $request->filled('day') ||
                                 ($isAdmin && $request->filled('branch_agent_id'));

            if ($request->boolean('archived')) {
                $query->archived();
            } elseif (!$hasFilterOrSearch) {
                $query->active();
            }

            // إذا لم يكن admin، قم بتصفية الوثائق حسب branch_agent_id
            if (!$isAdmin) {
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
            $documents = $query->orderBy('issue_date', 'desc')
                ->orderBy('id', 'desc')
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
     * Helper to get the authenticated user or the user specified in the header
     */
    private function getAuthenticatedUser(Request $request): ?User
    {
        // Try Sanctum auth first
        if ($request->user()) {
            return $request->user();
        }

        // Fallback to X-User-Id header
        $userId = $request->header('X-User-Id');
        if ($userId && is_numeric($userId)) {
            return User::find($userId);
        }

        return null;
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
                'chassis_number' => 'required|string|max:255',
                'plate_number_manual' => 'required|string|max:255',
                'vehicle_type_id' => 'required_unless:insurance_type,تأمين إجباري سيارات|nullable|exists:vehicle_types,id',
                'color' => 'required|string|max:255',
                'year' => 'required|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'fuel_type' => 'nullable|in:بنزين/Gasoline,ديزل/Diesel,كهرباء/Electric,غاز طبيعي/CNG,هيدروجين/Hydrogen',
                'license_purpose' => 'required_unless:insurance_type,تأمين سيارات أجنبية|nullable|in:خاصة/Private,عامة/Public,نقل/Transport,زراعي/Agricultural,صناعي/Industrial',
                'engine_power' => 'required_unless:insurance_type,تأمين طرف ثالث سيارات,تأمين سيارات أجنبية|nullable|string|max:255',
                'authorized_passengers' => 'nullable|integer|min:0|max:100',
                'load_capacity' => 'nullable|numeric|min:0|max:1000',
                'insured_name' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:255',
                'whatsapp_number' => 'required|string|min:10|max:255',
                'driving_license_number' => 'nullable|string|max:255',
                'nid_passport' => 'required|string|min:6|max:50',
                'nationality' => 'required|string|max:100',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'engine_number' => 'nullable|string|max:255',
                'engine_cc' => 'nullable|string|max:255',
                'vehicle_weight' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'premium' => 'required|numeric|min:0|max:999999',
                'third_party_purpose' => 'nullable|string|max:255',
                'foreign_car_country' => 'nullable|string|max:255',
                'foreign_car_purpose' => 'nullable|string|max:255',
                'print_type' => 'nullable|in:A5,A4',
                // EIDC Vehicle Classification (required for mandatory insurance)
                'eidc_vehicle_type_id' => 'nullable|string',
                'eidc_vehicle_spec_id' => 'nullable|string',
                'eidc_vehicle_detail_id' => 'nullable|string',
                'TypeOfVehicle' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي
            $lastDocument = InsuranceDocument::where('insurance_number', 'like', 'BKMCI%')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastDocument && preg_match('/BKMCI(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            do {
                $insuranceNumber = 'BKMCI' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (InsuranceDocument::where('insurance_number', $insuranceNumber)->exists());

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
            $premium = (float) ($validated['premium'] ?? 0);
            $tax = (float) ($request->input('tax', 1.000));
            $stamp = (float) ($request->input('stamp', 0.500));
            $issueFees = (float) ($request->input('issue_fees', 2.000));
            $supervisionFees = (float) ($request->input('supervision_fees', 0.500));
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
                $userId = is_numeric($userId) ? (int) $userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user) {
                        $isAdmin = $user->is_admin ?? false;
                        Log::info('User found:', [
                            'user_id' => $userId,
                            'is_admin' => $isAdmin,
                        ]);

                        if ($isAdmin) {
                            // Admin can specify any agent
                            $branchAgentId = $request->input('branch_agent_id');
                        } else {
                            // If not admin, force their own branch_agent_id
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            // If branch_agent_id is still null and we have it in request, and we didn't force it to null for non-admins
            if (!$branchAgentId && $request->has('branch_agent_id')) {
                $branchAgentId = $request->input('branch_agent_id');
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
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'driving_license_number' => $validated['driving_license_number'] ?? null,
                'nid_passport' => $validated['nid_passport'] ?? null,
                'nationality' => $validated['nationality'] ?? 'ليبي',
                'engine_number' => $validated['engine_number'] ?? null,
                'engine_cc' => $validated['engine_cc'] ?? null,
                'vehicle_weight' => $validated['vehicle_weight'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
                'print_type' => $validated['print_type'] ?? 'A4',
                'branch_agent_id' => $branchAgentId,
                // EIDC vehicle classification fields
                'eidc_vehicle_type_id' => $validated['eidc_vehicle_type_id'] ?? null,
                'eidc_vehicle_spec_id' => $validated['eidc_vehicle_spec_id'] ?? null,
                'eidc_vehicle_detail_id' => $validated['eidc_vehicle_detail_id'] ?? null,
                // Start with pending if mandatory insurance
                'eidc_sync_status' => ($validated['insurance_type'] === 'تأمين إجباري سيارات') ? 'pending' : null,
            ]);

            // ─── EIDC Integration: Register on Authority System ────────────────
            if ($validated['insurance_type'] === 'تأمين إجباري سيارات' && config('eidc.enabled', true)) {
                $this->syncWithEidc($document, $validated, $endDate, $user ?? null);
            }

            return response()->json($document->fresh()->load(['plate.city', 'vehicleType']), 201);
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
                'chassis_number' => 'required|string|max:255',
                'plate_number_manual' => 'required|string|max:255',
                'vehicle_type_id' => 'required_unless:insurance_type,تأمين إجباري سيارات|nullable|exists:vehicle_types,id',
                'color' => 'required|string|max:255',
                'year' => 'required|integer|min:1960|max:2026',
                'manufacturing_country' => 'nullable|string|max:255',
                'fuel_type' => 'nullable|in:بنزين/Gasoline,ديزل/Diesel,كهرباء/Electric,غاز طبيعي/CNG,هيدروجين/Hydrogen',
                'license_purpose' => 'required_unless:insurance_type,تأمين سيارات أجنبية|nullable|in:خاصة/Private,عامة/Public,نقل/Transport,زراعي/Agricultural,صناعي/Industrial',
                'engine_power' => 'required_unless:insurance_type,تأمين طرف ثالث سيارات,تأمين سيارات أجنبية|nullable|string|max:255',
                'authorized_passengers' => 'nullable|integer|min:0|max:100',
                'load_capacity' => 'nullable|numeric|min:0|max:1000',
                'insured_name' => 'required|string|max:255',
                'phone' => 'required|string|min:10|max:255',
                'whatsapp_number' => 'required|string|min:10|max:255',
                'driving_license_number' => 'nullable|string|max:255',
                'nid_passport' => 'required|string|min:6|max:255',
                'nationality' => 'required|string|max:100',
                'premium' => 'required|numeric|min:0|max:999999',
                'third_party_purpose' => 'nullable|string|max:255',
                'foreign_car_country' => 'nullable|string|max:255',
                'foreign_car_purpose' => 'nullable|string|max:255',
                'print_type' => 'nullable|in:A5,A4',
                'email' => 'required|email|max:255',
                'address' => 'required|string|max:255',
                'engine_number' => 'nullable|string|max:255',
                'engine_cc' => 'nullable|string|max:255',
                'vehicle_weight' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
                'TypeOfVehicle' => 'nullable|string',
                'eidc_vehicle_type_id' => 'required_if:insurance_type,تأمين إجباري سيارات|nullable|string',
                'eidc_vehicle_spec_id' => 'required_if:insurance_type,تأمين إجباري سيارات|nullable|string',
                'eidc_vehicle_detail_id' => 'nullable|string',
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
                'whatsapp_number' => $validated['whatsapp_number'] ?? null,
                'driving_license_number' => $validated['driving_license_number'] ?? null,
                'nid_passport' => $validated['nid_passport'] ?? null,
                'nationality' => $validated['nationality'] ?? 'ليبي',
                'premium' => $premium,
                'tax' => $tax,
                'stamp' => $stamp,
                'issue_fees' => $issueFees,
                'supervision_fees' => $supervisionFees,
                'total' => $total,
                'print_type' => $validated['print_type'] ?? 'A4',
                'email' => $validated['email'] ?? null,
                'address' => $validated['address'] ?? null,
                'engine_number' => $validated['engine_number'] ?? null,
                'engine_cc' => $validated['engine_cc'] ?? null,
                'vehicle_weight' => $validated['vehicle_weight'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'eidc_vehicle_type_id' => $validated['eidc_vehicle_type_id'] ?? null,
                'eidc_vehicle_spec_id' => $validated['eidc_vehicle_spec_id'] ?? null,
                'eidc_vehicle_detail_id' => $validated['eidc_vehicle_detail_id'] ?? null,
            ]);

            // ─── EIDC Integration: Update on Authority System ────────────────
            if ($document->insurance_type === 'تأمين إجباري سيارات' && config('eidc.enabled', true)) {
                // نحتاج لتمرير البيانات المطلوبة لـ syncWithEidc
                // ملاحظة: قد نحتاج لإضافة حقول EIDC المفقودة في validation الـ update
                $syncData = array_merge($validated, [
                    'eidc_vehicle_type_id' => $request->input('eidc_vehicle_type_id') ?? $document->eidc_vehicle_type_id,
                    'eidc_vehicle_spec_id' => $request->input('eidc_vehicle_spec_id') ?? $document->eidc_vehicle_spec_id,
                    'eidc_vehicle_detail_id' => $request->input('eidc_vehicle_detail_id') ?? $document->eidc_vehicle_detail_id,
                    'nid_passport' => $validated['nid_passport'] ?? $document->nid_passport ?? '',
                    'nationality' => $validated['nationality'] ?? $document->nationality ?? 'ليبي',
                ]);
                $this->syncWithEidc($document, $syncData, $endDate, $user ?? null);
            }

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
                'vehicle_type' => $document->vehicleType ? ($document->insurance_type === 'تأمين إجباري سيارات' ? $document->vehicleType->brand : ($document->vehicleType->brand . ($document->vehicleType->category ? ' / ' . $document->vehicleType->category : ''))) : '-',
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

            if ($plateNumber && $portNumber && str_ends_with($plateNumber, '-' . $portNumber)) {
                $plateNumber = substr($plateNumber, 0, -strlen('-' . $portNumber));
            }

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

        if ($plateNumber && $cityOrder && str_ends_with($plateNumber, '-' . $cityOrder)) {
            $plateNumber = substr($plateNumber, 0, -strlen('-' . $cityOrder));
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
        $parts = explode('.', (string) $number);
        $integerPart = (int) ($parts[0] ?? 0);
        $decimalPart = isset($parts[1]) ? (int) ($parts[1]) : 0;

        // تحويل الجزء الصحيح
        $words = '';

        if ($integerPart == 0 && $decimalPart == 0) {
            return 'صفر دينار';
        }

        if ($integerPart > 0) {
            $num = $integerPart;

            // الآلاف
            if ($num >= 1000) {
                $thousands = (int) ($num / 1000);
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
                $hundred = (int) ($num / 100);
                if ($hundred <= 9 && isset($hundreds[$hundred])) {
                    $words .= $hundreds[$hundred] . ' ';
                } else {
                    $words .= number_format($hundred) . ' مائة ';
                }
                $num = $num % 100;
            }

            // العشرات والآحاد
            if ($num >= 20) {
                $ten = (int) ($num / 10);
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
        $num = (int) $decimal;

        if ($num == 0) {
            return '';
        }

        // المئات
        if ($num >= 100) {
            $hundred = (int) ($num / 100);
            if ($hundred <= 9 && isset($hundreds[$hundred])) {
                $words .= $hundreds[$hundred] . ' ';
            } else {
                $words .= number_format($hundred) . ' مائة ';
            }
            $num = $num % 100;
        }

        // العشرات والآحاد
        if ($num >= 20) {
            $ten = (int) ($num / 10);
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

    private static function cleanPhone(?string $phone): string
    {
        if (!$phone) {
            return '';
        }
        // Remove spaces, dashes, parentheses, plus signs
        $cleaned = preg_replace('/[\s\-\(\)\+]/', '', $phone);
        
        if (strpos($cleaned, '00218') === 0) {
            $cleaned = '0' . substr($cleaned, 5);
        } elseif (strpos($cleaned, '218') === 0) {
            $cleaned = '0' . substr($cleaned, 3);
        }
        
        return $cleaned;
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // EIDC Integration Methods - تكامل مع هيئة الإشراف على التأمين
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Sync a mandatory insurance document with the EIDC authority system
     * يسجل الوثيقة في نظام الهيئة بعد حفظها محلياً
     */
    private function syncWithEidc(InsuranceDocument $document, array $validated, ?string $endDate, ?User $issuingUser = null): void
    {
        try {
            // Ensure vehicleType relation is loaded for TypeOfVehicle fallback
            $document->loadMissing('vehicleType');

            $user = null;
            if ($document->branch_agent_id) {
                $document->loadMissing('branchAgent.user');
                $user = $document->branchAgent->user ?? null;
            }

            // Fallback to issuing user if no agent user is found
            if (!$user) {
                $user = $issuingUser;
            }

            Log::info('EIDC: Using credentials for user', [
                'user_id' => $user->id ?? 'system',
                'email' => $user->email ?? 'N/A',
                'eidc_email' => $user->eidc_username ?? 'N/A', // Using eidc_username
                'is_agent' => (bool) ($document->branch_agent_id)
            ]);

            $eidc = (new EidcApiService())->forUser($user);

            // Log the actual username being used by the service (masked)
            $serviceUsername = $eidc->getUsername();
            Log::info('EIDC: Service will use username: ' . substr($serviceUsername, 0, 3) . '***');

            // Use the actual issue_fees from the document to match the calculated total
            $issueFees = (float) ($document->issue_fees ?: ($validated['issue_fees'] ?? 2.0));

            // التحقق من وجود البيانات الإجبارية للهيئة قبل الإرسال (تأمين إجباري فقط)
            if ($document->insurance_type === 'تأمين إجباري سيارات') {
                $nid = $validated['nid_passport'] ?? $document->nid_passport;
                $vType = $validated['eidc_vehicle_type_id'] ?? $document->eidc_vehicle_type_id;
                $vSpec = $validated['eidc_vehicle_spec_id'] ?? $document->eidc_vehicle_spec_id;

                if (empty($nid) || empty($vType) || empty($vSpec)) {
                    throw new \Exception('بيانات الهيئة ناقصة (الرقم الوطني أو تصنيفات المركبة). يرجى تعديل الوثيقة وإكمال البيانات قبل المزامنة.');
                }
            }

            // تحضير بيانات الطلب بصيغة الهيئة (PascalCase keys as seen in error responses)
            $payload = [
                'InsuredsName' => $validated['insured_name'] ?? $document->insured_name,
                'NidPassport' => $validated['nid_passport'] ?? $document->nid_passport ?? $validated['driving_license_number'] ?? $document->driving_license_number ?? '',
                'PhoneNo' => self::cleanPhone($validated['phone'] ?? $document->phone),
                'Nationality' => $validated['nationality'] ?? $document->nationality ?? 'ليبي',
                'Email' => $validated['email'] ?? $document->email ?? null,
                'Address' => $document->address ?: ($document->plate ? ($document->plate->city->name_ar ?? 'ليبيا') : 'ليبيا'),
                'FromNoonOf' => Carbon::parse($validated['start_date'] ?? $document->start_date)->isSameDay(now()) || Carbon::parse($validated['start_date'] ?? $document->start_date)->isPast()
                    ? now()->addDay()->setTime(12, 0, 0)->toIso8601String()
                    : Carbon::parse($validated['start_date'] ?? $document->start_date)->setTime(12, 0, 0)->toIso8601String(),
                'PurposeLicense' => EidcApiService::mapPurposeLicense($validated['license_purpose'] ?? $document->license_purpose ?? 'خاصة'),
                'DayOfCarType' => EidcApiService::mapDurationToDays($validated['duration'] ?? $document->duration ?? 'سنة'),
                'TypeVechicleId' => $validated['eidc_vehicle_type_id'] ?? $document->eidc_vehicle_type_id ?? '',
                'TypeVechicle2Id' => $validated['eidc_vehicle_spec_id'] ?? $document->eidc_vehicle_spec_id ?? '',
                'TypeVechicle3Id' => $validated['eidc_vehicle_detail_id'] ?? $document->eidc_vehicle_detail_id ?? null,
                'TypeOfVehicle' => !empty($validated['TypeOfVehicle']) ? $validated['TypeOfVehicle'] : ($document->vehicleType ? $document->vehicleType->brand : ''),
                'IssuingFeesOptions' => $issueFees,
                'PlateNo' => substr($validated['plate_number_manual'] ?? $document->plate_number_manual ?? '', 0, 20),
                'ChassisNo' => $validated['chassis_number'] ?? $document->chassis_number ?? null,
                'Color' => $validated['color'] ?? $document->color ?? null,
                'YearMade' => (int) ($validated['year'] ?? $document->year ?? date('Y')),
                'PassengersNo' => (int) ($validated['authorized_passengers'] ?? $document->authorized_passengers ?? 0),
                'EngineHp' => (int) (preg_replace('/[^0-9]/', '', $validated['engine_power'] ?? $document->engine_power ?? '0')),
                'Tonnage' => max(0, min(1000, (float) ($validated['load_capacity'] ?? $document->load_capacity ?? 0))),
                'RegAuthority' => $document->plate ? ($document->plate->city->name_ar ?? null) : null,
            ];

            Log::info('EIDC: Sending request to Authority', [
                'document_id' => $document->id,
                'insurance_number' => $document->insurance_number,
                'policy_id' => $document->eidc_policy_id, // قد يكون موجوداً في حالة التحديث
                'payload' => $payload,
            ]);

            // ─── جلب الأسعار النهائية من الهيئة قبل الإصدار ────────────────
            // لضمان تطابق المبالغ المالية في منظومتنا مع منظومة الهيئة، نقوم بعمل inquiry
            // إذا كانت هذه إضافة جديدة (ليست تحديثاً)
            if (!$document->eidc_policy_id) {
                try {
                    $inquiryResult = $eidc->inquiryPolicy($payload);
                    Log::info('EIDC: Inquiry result before creation', ['result' => $inquiryResult]);

                    if (!empty($inquiryResult['success']) || isset($inquiryResult['netPremium']) || isset($inquiryResult['NetPremium'])) {
                        $netPremium = (float) ($inquiryResult['netPremium'] ?? $inquiryResult['net_premium'] ?? $inquiryResult['NetPremium'] ?? $inquiryResult['premiumYear'] ?? 0);
                        $tax = (float) ($inquiryResult['tax'] ?? $inquiryResult['tax_amount'] ?? $inquiryResult['Tax'] ?? 1.0);
                        $stamp = (float) ($inquiryResult['stamp'] ?? $inquiryResult['stamp_amount'] ?? $inquiryResult['Stamp'] ?? 0.25);
                        $supervision = (float) ($inquiryResult['supervisionFees'] ?? $inquiryResult['supervision_fees'] ?? $inquiryResult['SupervisionFees'] ?? 0.35);
                        $issue = (float) ($inquiryResult['issuingFees'] ?? $inquiryResult['issue_fees'] ?? $inquiryResult['IssuingFees'] ?? 2.0);
                        $total = (float) ($inquiryResult['totalPremium'] ?? $inquiryResult['total'] ?? $inquiryResult['TotalPremium'] ?? ($netPremium + $tax + $stamp + $supervision + $issue));

                        if ($total > 0) {
                            $document->update([
                                'premium' => $netPremium,
                                'tax' => $tax,
                                'stamp' => $stamp,
                                'supervision_fees' => $supervision,
                                'issue_fees' => $issue,
                                'total' => $total
                            ]);
                            Log::info('EIDC: Local document financial data synchronized with Authority inquiry', [
                                'document_id' => $document->id,
                                'total' => $total
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('EIDC: Inquiry failed during sync, using existing data: ' . $e->getMessage());
                }
            }

            if ($document->eidc_policy_id) {
                // ─── حالة التحديث: تعديل بيانات المؤمن له فقط عبر PATCH ────────────────

                $policyId = $document->eidc_policy_id;
                $expectedUpdatedAt = null;

                // التحقق مما إذا كان المعرف هو GUID أو رقم الوثيقة (transactionCode)
                if (!preg_match('/^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i', $policyId)) {
                    Log::info('EIDC: policy_id is not a GUID, attempting to resolve it', [
                        'policy_number' => $policyId
                    ]);

                    $policyData = $eidc->getPolicyDataByNumber($policyId);

                    if ($policyData) {
                        Log::info('EIDC: Resolved GUID for policy', ['guid' => $policyData['id']]);
                        $policyId = $policyData['id'];
                        $expectedUpdatedAt = $policyData['updatedAt'];
                        // تحديث المعرف في قاعدة البيانات ليكون GUID مستقبلاً
                        $document->update(['eidc_policy_id' => $policyId]);
                    } else {
                        Log::error('EIDC: Could not resolve GUID for policy number', ['policy_number' => $policyId]);
                    }
                } else {
                    // إذا كان لدينا GUID مسبقاً، نحتاج لجلب updatedAt الحالي من الهيئة لضمان نجاح الـ PATCH (concurrency)
                    $policyData = $eidc->getPolicyDataByNumber($document->eidc_transaction_code ?: $policyId);
                    if ($policyData) {
                        $expectedUpdatedAt = $policyData['updatedAt'];
                    }
                }

                $updatePayload = [
                    'InsuredsName' => $payload['InsuredsName'],
                    'NidPassport' => $payload['NidPassport'] ?: ($document->nid_passport ?: ''),
                    'PhoneNo' => $payload['PhoneNo'],
                    'Nationality' => $payload['Nationality'],
                    'Email' => $payload['Email'],
                    'Address' => $payload['Address'] ?? '',
                    'ExpectedUpdatedAt' => $expectedUpdatedAt, // التوكن المطلوب لضمان التزامن
                ];

                Log::info('EIDC: Sending PATCH to update insured data', [
                    'document_id' => $document->id,
                    'policy_id' => $policyId,
                    'payload' => $updatePayload,
                ]);

                $response = $eidc->updateInsured($policyId, $updatePayload);
            } else {
                // حالة الإضافة: إنشاء وثيقة جديدة
                $response = $eidc->createPolicy($payload);
            }

            Log::info('EIDC: Authority response', [
                'document_id' => $document->id,
                'response' => $response,
            ]);

            if (!empty($response['success'])) {
                $isUpdate = (bool) $document->eidc_policy_id;

                if ($isUpdate) {
                    // PATCH نجح — نحفظ الحالة فقط (لا نغير policy_id لأن الهيئة لا ترجع transactionCode في PATCH)
                    $updateData = [
                        'eidc_sync_status' => 'synced',
                        'eidc_error' => null,
                        'eidc_synced_at' => now(),
                    ];
                } else {
                    // إنشاء جديد — نحفظ transactionCode و pdfUrl
                    $transactionCode = $response['transactionCode'] ?? null;
                    $guid = null;

                    // محاولة جلب الـ GUID فوراً لتسهيل عمليات التحديث (PATCH) مستقبلاً
                    if ($transactionCode) {
                        Log::info('EIDC: Policy created, attempting to resolve GUID immediately', ['transactionCode' => $transactionCode]);
                        $guid = $eidc->getPolicyIdByNumber($transactionCode);
                    }

                    $updateData = [
                        'eidc_sync_status' => 'synced',
                        'eidc_error' => null,
                        'eidc_synced_at' => now(),
                        'eidc_policy_id' => $guid ?: $transactionCode,
                        'eidc_transaction_code' => $transactionCode,
                        'eidc_pdf_url' => $response['pdfUrl'] ?? null,
                    ];
                }

                $document->update($updateData);

                Log::info('EIDC: Policy synchronized successfully', [
                    'document_id' => $document->id,
                    'is_update' => $isUpdate,
                    'policy_id' => $document->eidc_policy_id,
                ]);
            } else {
                // فشل التسجيل
                $errorMsg = $response['message'] ?? 'خطأ غير معروف من نظام الهيئة';
                $document->update([
                    // إذا كان مربوطاً مسبقاً، نحافظ على حالة synced لتجنب الارتباك، ولكن نسجل الخطأ
                    'eidc_sync_status' => $document->eidc_policy_id ? 'synced' : 'failed',
                    'eidc_error' => $errorMsg,
                ]);

                Log::error('EIDC: Policy sync failed', [
                    'document_id' => $document->id,
                    'error' => $errorMsg,
                    'warnings' => $response['warnings'] ?? [],
                ]);
            }
        } catch (\Exception $e) {
            // خطأ في الاتصال - الوثيقة محفوظة محلياً لكن لم تُسجَّل في الهيئة
            $document->update([
                'eidc_sync_status' => 'failed',
                'eidc_error' => 'خطأ في الاتصال: ' . $e->getMessage(),
            ]);

            Log::error('EIDC: Exception during sync', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * POST /insurance-documents/{id}/eidc-cancel
     * إلغاء وثيقة في نظام الهيئة
     */
    public function eidcCancel(Request $request, string $id)
    {
        try {
            $document = InsuranceDocument::findOrFail($id);

            if (!$document->eidc_policy_id) {
                return response()->json(['message' => 'هذه الوثيقة غير مسجلة في نظام الهيئة'], 422);
            }

            $reason = $request->input('reason', 'إلغاء الوثيقة');

            $user = $this->getAuthenticatedUser($request);
            $eidc = (new EidcApiService())->forUser($user);
            $response = $eidc->cancelPolicy($document->eidc_policy_id, $reason);

            if (!empty($response['success'])) {
                $document->update(['eidc_sync_status' => 'cancelled']);
                return response()->json([
                    'message' => 'تم إلغاء الوثيقة في نظام الهيئة بنجاح',
                    'replacementSerialCode' => $response['replacementSerialCode'] ?? null,
                ]);
            }

            return response()->json(['message' => $response['message'] ?? 'فشل الإلغاء في نظام الهيئة'], 400);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Exception $e) {
            Log::error('EIDC cancel error: ' . $e->getMessage());
            return response()->json(['message' => 'فشل إلغاء الوثيقة في نظام الهيئة', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /insurance-documents/{id}/eidc-retry
     * إعادة محاولة المزامنة مع الهيئة لوثيقة فاشلة
     */
    public function eidcRetrySync(Request $request, string $id)
    {
        try {
            $document = InsuranceDocument::findOrFail($id);

            if ($document->insurance_type !== 'تأمين إجباري سيارات') {
                return response()->json(['message' => 'هذه الوثيقة ليست تأمين إجباري سيارات'], 422);
            }

            if ($document->eidc_sync_status === 'synced') {
                return response()->json(['message' => 'الوثيقة مسجلة بالفعل في نظام الهيئة']);
            }

            $user = $this->getAuthenticatedUser($request);

            // Build validated array from document
            $validated = [
                'insured_name' => $document->insured_name,
                'nid_passport' => $document->nid_passport,
                'driving_license_number' => $document->driving_license_number,
                'phone' => $document->phone,
                'start_date' => $document->start_date ? Carbon::parse($document->start_date)->format('Y-m-d') : now()->format('Y-m-d'),
                'license_purpose' => $document->license_purpose ?? 'خاصة',
                'duration' => $document->duration ?? 'سنة',
                'eidc_vehicle_type_id' => $document->eidc_vehicle_type_id,
                'eidc_vehicle_spec_id' => $document->eidc_vehicle_spec_id,
                'eidc_vehicle_detail_id' => $document->eidc_vehicle_detail_id,
                'issue_fees' => $document->issue_fees,
                'plate_number_manual' => $document->plate_number_manual,
                'chassis_number' => $document->chassis_number,
                'color' => $document->color,
                'year' => $document->year,
                'authorized_passengers' => $document->authorized_passengers,
                'engine_power' => $document->engine_power,
                'load_capacity' => $document->load_capacity,
            ];

            $document->update(['eidc_sync_status' => 'pending', 'eidc_error' => null]);
            $this->syncWithEidc($document, $validated, $document->end_date ? Carbon::parse($document->end_date)->format('Y-m-d') : null, $user);

            $document->refresh();
            if ($document->eidc_sync_status !== 'synced') {
                return response()->json([
                    'message' => 'فشلت إعادة المحاولة: ' . $document->eidc_error,
                    'eidc_sync_status' => $document->eidc_sync_status,
                ], 400);
            }

            return response()->json([
                'message' => 'تم التسجيل في نظام الهيئة بنجاح',
                'eidc_sync_status' => $document->eidc_sync_status,
                'eidc_policy_id' => $document->eidc_policy_id,
                'eidc_pdf_url' => $document->eidc_pdf_url,
            ]);
        } catch (\Exception $e) {
            Log::error('EIDC Retry Sync Error: ' . $e->getMessage());
            return response()->json(['message' => 'فشلت إعادة المحاولة', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /insurance-documents/eidc-sync-all
     * مزامنة الوثائق من نظام الهيئة إلى النظام المحلي
     */
    public function eidcSyncFromAuthority(Request $request)
    {
        try {
            $user = $this->getAuthenticatedUser($request);

            // Determine which agent(s) to sync
            $agentsToSync = [];

            if ($user && $user->is_admin) {
                $requestedAgentId = $request->input('branch_agent_id') ?: $request->query('branch_agent_id');
                if ($requestedAgentId) {
                    $agent = BranchAgent::with('user')->find($requestedAgentId);
                    if ($agent && $agent->user) {
                        $agentsToSync[] = $agent;
                    }
                } else {
                    // Admin wants to sync for ALL agents that have EIDC credentials
                    $agentsToSync = BranchAgent::whereHas('user', function ($q) {
                        $q->whereNotNull('eidc_username')->where('eidc_username', '!=', '');
                    })->with('user')->get();
                }
            } else {
                // Regular agent user - sync only for themselves
                if ($user) {
                    $agent = BranchAgent::with('user')->where('user_id', $user->id)->first();
                    if ($agent) {
                        $agentsToSync[] = $agent;
                    }
                }
            }

            if (empty($agentsToSync)) {
                return response()->json(['message' => 'لم يتم العثور على وكلاء لمزامنتهم'], 400);
            }

            $syncedCount = 0;
            $newCount = 0;
            $agentNames = [];

            foreach ($agentsToSync as $agent) {
                $agentUser = $agent->user;
                if (!$agentUser || empty($agentUser->eidc_username)) {
                    continue;
                }

                $agentNames[] = $agent->agency_name ?: $agentUser->name;

                try {
                    $eidc = (new EidcApiService())->forUser($agentUser);

                    // جلب آخر 100 وثيقة من الهيئة لضمان مزامنة كل الوثائق المفقودة
                    $response = $eidc->getPolicies(['per_page' => 100]);

                    // Extract policies array correctly from items
                    $policies = $response['items'] ?? $response['data'] ?? $response ?? [];
                    if (!is_array($policies)) {
                        continue;
                    }

                    foreach ($policies as $policy) {
                        $policyId = $policy['id'] ?? $policy['policyId'] ?? null;
                        if (!$policyId) {
                            continue;
                        }

                        $policyNo = $policy['policyNo'] ?? $policy['transactionCode'] ?? null;
                        $insuranceNumber = $policyNo ? 'EIDC-' . $policyNo : 'EIDC-' . $policyId;

                        // البحث عن الوثيقة محلياً
                        $exists = InsuranceDocument::where('eidc_policy_id', $policyId)
                            ->orWhere('eidc_transaction_code', $policyId)
                            ->orWhere('eidc_transaction_code', $policyNo)
                            ->orWhere('insurance_number', $insuranceNumber)
                            ->exists();

                        if (!$exists) {
                            // Extract financial values
                            $netPremium = (float) ($policy['netPremium'] ?? $policy['net_premium'] ?? $policy['NetPremium'] ?? 0);
                            $tax = (float) ($policy['tax'] ?? $policy['tax_amount'] ?? $policy['Tax'] ?? 1.0);
                            $stamp = (float) ($policy['stamp'] ?? $policy['stamp_amount'] ?? $policy['Stamp'] ?? 0.5);
                            $supervision = (float) ($policy['supervisionFees'] ?? $policy['supervision_fees'] ?? $policy['SupervisionFees'] ?? 0.5);
                            $issue = (float) ($policy['issuingFees'] ?? $policy['issue_fees'] ?? $policy['IssuingFees'] ?? 2.0);
                            $total = (float) ($policy['totalPremium'] ?? $policy['totalAmount'] ?? $policy['total'] ?? ($netPremium + $tax + $stamp + $supervision + $issue));

                            // Try to resolve plate_id from EIDC regAuthority
                            $plateId = null;
                            if (!empty($policy['regAuthority'])) {
                                $plate = \App\Models\Plate::whereHas('city', function ($q) use ($policy) {
                                    $q->where('name_ar', 'like', '%' . $policy['regAuthority'] . '%');
                                })->first();
                                if ($plate) {
                                    $plateId = $plate->id;
                                }
                            }

                            // Map days to standard duration strings
                            $days = isset($policy['dayOfCarType']) ? (int) $policy['dayOfCarType'] : 365;
                            $duration = 'سنة';
                            if ($days === 730) {
                                $duration = 'سنتين';
                            } elseif ($days === 90) {
                                $duration = 'ثلاثة أشهر (90 يوم)';
                            } elseif ($days === 60) {
                                $duration = 'شهرين (60 يوم)';
                            } elseif ($days === 30) {
                                $duration = 'شهر (30 يوم)';
                            } elseif ($days === 15) {
                                $duration = 'أسبوعين (15 يوم)';
                            }

                            // إنشاء وثيقة جديدة من بيانات الهيئة وربطها بالوكيل
                            InsuranceDocument::create([
                                'insurance_type' => 'تأمين إجباري سيارات',
                                'insurance_number' => $insuranceNumber,
                                'issue_date' => isset($policy['createdAt']) ? Carbon::parse($policy['createdAt'])->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                                'plate_id' => $plateId,
                                'start_date' => isset($policy['fromNoonOf']) ? Carbon::parse($policy['fromNoonOf'])->format('Y-m-d') : now()->format('Y-m-d'),
                                'end_date' => isset($policy['fromNoonOf']) ? Carbon::parse($policy['fromNoonOf'])->addDays($days)->format('Y-m-d') : now()->addYear()->format('Y-m-d'),
                                'duration' => $duration,
                                'insured_name' => $policy['insuredsName'] ?? 'مستورد من الهيئة',
                                'phone' => $policy['phoneNo'] ?? '-',
                                'nid_passport' => $policy['nidPassport'] ?? null,
                                'nationality' => $policy['nationality'] ?? 'ليبي',
                                'email' => $policy['email'] ?? null,
                                'address' => $policy['address'] ?? null,
                                'chassis_number' => $policy['chassisNo'] ?? '-',
                                'plate_number_manual' => $policy['plateNo'] ?? '-',
                                'color' => $policy['color'] ?? '-',
                                'year' => isset($policy['yearMade']) ? (int) $policy['yearMade'] : null,
                                'authorized_passengers' => isset($policy['passengersNo']) ? (int) $policy['passengersNo'] : null,
                                'engine_power' => isset($policy['engineHp']) ? ($policy['engineHp'] . ' حصان') : null,
                                'load_capacity' => isset($policy['tonnage']) ? (float) $policy['tonnage'] : null,
                                'eidc_policy_id' => $policyId,
                                'eidc_transaction_code' => $policyNo ?: $policyId,
                                'eidc_sync_status' => 'synced',
                                'eidc_synced_at' => isset($policy['createdAt']) ? Carbon::parse($policy['createdAt'])->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
                                'premium' => $netPremium,
                                'tax' => $tax,
                                'stamp' => $stamp,
                                'supervision_fees' => $supervision,
                                'issue_fees' => $issue,
                                'total' => $total,
                                'branch_agent_id' => $agent->id,
                                'notes' => 'وثيقة مستوردة تلقائياً من منظومة الهيئة',
                                'eidc_vehicle_type_id' => $policy['typeVechicleId'] ?? null,
                                'eidc_vehicle_spec_id' => $policy['typeVechicle2Id'] ?? null,
                                'eidc_vehicle_detail_id' => $policy['typeVechicle3Id'] ?? null,
                            ]);
                            $newCount++;
                        }
                        $syncedCount++;
                    }
                } catch (\Exception $e) {
                    Log::error("EIDC Sync failed for agent {$agent->id}: " . $e->getMessage());
                }
            }

            $agentsListStr = implode('، ', $agentNames);
            return response()->json([
                'message' => "تمت المزامنة بنجاح للوكلاء ({$agentsListStr}). تم فحص {$syncedCount} وثيقة، وإضافة {$newCount} وثيقة جديدة.",
                'new_count' => $newCount,
                'synced_count' => $syncedCount
            ]);
        } catch (\Exception $e) {
            Log::error('EIDC Global Sync Error: ' . $e->getMessage());
            return response()->json(['message' => 'فشلت عملية المزامنة الكلية', 'error' => $e->getMessage()], 500);
        }
    }

    // ─── EIDC Lookups ─────────────────────────────────────────────────────────

    /**
     * Resolve the user for EIDC: prefer Sanctum auth, fallback to X-User-Id header.
     * EIDC routes are not under auth:sanctum, so $request->user() may be null.
     */
    private function resolveEidcUser(Request $request): ?User
    {
        // First try Sanctum authenticated user
        $user = $request->user();
        if ($user) {
            return $user;
        }

        // Fallback: X-User-Id header sent by the frontend
        $userId = $request->header('X-User-Id');
        if ($userId && is_numeric($userId)) {
            return User::find((int) $userId);
        }

        return null;
    }

    public function eidcVehicleTypes(Request $request)
    {
        try {
            $service = (new EidcApiService())->forUser($this->resolveEidcUser($request));
            $types = $service->getVehicleTypes();
            return response()->json($types);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function eidcVehicleSpecs(Request $request)
    {
        $typeId = $request->query('typeId');
        if (!$typeId)
            return response()->json([]);

        try {
            $service = (new EidcApiService())->forUser($this->resolveEidcUser($request));
            $specs = $service->getVehicleSpecs($typeId);
            return response()->json($specs);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function eidcVehicleDetails(Request $request)
    {
        $typeId = $request->query('typeId');
        if (!$typeId)
            return response()->json([]);

        try {
            $service = (new EidcApiService())->forUser($this->resolveEidcUser($request));
            $details = $service->getVehicleDetails($typeId);
            return response()->json($details);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function eidcInquiry(Request $request)
    {
        try {
            $service = (new EidcApiService())->forUser($this->resolveEidcUser($request));
            $data = $request->all();

            // Clean phone number format for EIDC API
            if (isset($data['PhoneNo'])) {
                $data['PhoneNo'] = self::cleanPhone($data['PhoneNo']);
            }

            // Fix: Ensure FromNoonOf is at least tomorrow (Resolution 126/2022)
            if (isset($data['FromNoonOf'])) {
                try {
                    $startDate = \Carbon\Carbon::parse($data['FromNoonOf']);
                    if ($startDate->isSameDay(now()) || $startDate->isPast()) {
                        $data['FromNoonOf'] = now()->addDay()->setTime(12, 0, 0)->toIso8601String();
                    } else {
                        $data['FromNoonOf'] = $startDate->setTime(12, 0, 0)->toIso8601String();
                    }
                } catch (\Exception $e) {
                    Log::warning('EIDC: Could not parse FromNoonOf in inquiry: ' . $data['FromNoonOf']);
                }
            } else {
                // If not set, default to tomorrow
                $data['FromNoonOf'] = now()->addDay()->setTime(12, 0, 0)->toIso8601String();
            }

            $result = $service->inquiryPolicy($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function eidcSerialStats(Request $request)
    {
        try {
            $service = (new EidcApiService())->forUser($request->user());
            $stats = $service->getSerialCodeStats();
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Proxy for EIDC PDF to avoid CORS, Cookie and caching issues
     */
    public function eidcPrintProxy(string $id)
    {
        try {
            $document = InsuranceDocument::findOrFail($id);
            if (!$document->eidc_pdf_url) {
                return response()->json(['message' => 'رابط وثيقة الهيئة غير موجود'], 404);
            }

            // Clean the URL and FORCE HTTPS to avoid port 80 issues
            $url = explode('?', $document->eidc_pdf_url)[0];
            $url = str_replace('http://', 'https://', $url);

            Log::info("EIDC: Proxying PDF for document {$id}", ['url' => $url]);

            // Attempt to fetch the PDF from Authority with HTTPS and longer timeout
            $response = Http::timeout(60)->connectTimeout(15)->get($url);

            if (!$response->successful()) {
                Log::error("EIDC: Proxy failed to fetch PDF", [
                    'status' => $response->status(),
                    'url' => $url,
                    'body' => substr($response->body(), 0, 200)
                ]);
                return response()->json(['message' => 'فشل جلب الوثيقة من نظام الهيئة. قد يكون الخادم لديهم متوقف حالياً.'], 502);
            }

            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="eidc-policy-' . $document->insurance_number . '.pdf"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            Log::error("EIDC: Proxy exception", ['error' => $e->getMessage()]);
            return response()->json(['message' => 'حدث خطأ أثناء جلب الوثيقة: ' . $e->getMessage()], 500);
        }
    }
}
