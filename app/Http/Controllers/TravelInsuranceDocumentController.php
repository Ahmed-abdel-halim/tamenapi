<?php

namespace App\Http\Controllers;

use App\Models\TravelInsuranceDocument;
use App\Models\TravelInsurancePassenger;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TravelInsuranceDocumentController extends Controller
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
                            // إذا لم يكن admin، احصل على branch_agent_id من المستخدم أو الموظف التابع له
                            $branchAgentId = $user->branch_agent_id;
                            if (!$branchAgentId) {
                                $branchAgent = BranchAgent::where('user_id', $userId)->first();
                                if ($branchAgent) {
                                    $branchAgentId = $branchAgent->id;
                                }
                            }
                        }
                    }
                }
            }

            // بناء الاستعلام
            $query = TravelInsuranceDocument::with(['passengers', 'branchAgent', 'user']);
            
            $hasFilterOrSearch = $request->filled('search') || 
                                 $request->filled('year') || 
                                 $request->filled('month') || 
                                 $request->filled('day') ||
                                 ($isAdmin && $request->filled('branch_agent_id'));

            $statusParam = $request->query('status');
            if ($statusParam === 'all') {
                // Return all documents (both active and expired)
            } elseif ($statusParam === 'expired' || $statusParam === 'archived' || $request->boolean('archived')) {
                $query->archived();
            } elseif ($statusParam === 'active') {
                $query->active();
            } elseif (!$hasFilterOrSearch) {
                $query->active();
            }

            // إذا لم يكن admin، قم بتصفية الوثائق حسب branch_agent_id
            if (!$isAdmin) {
                if ($branchAgentId) {
                    $query->where('branch_agent_id', $branchAgentId);
                } else {
                    $query->where('user_id', $userId);
                }
            }

            // إضافة ميزة البحث
            $search = $request->query('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('insurance_number', 'like', "%{$search}%")
                      ->orWhereHas('passengers', function ($pq) use ($search) {
                          $pq->where('is_main_passenger', true)
                             ->where(function ($sq) use ($search) {
                                 $sq->where('name_ar', 'like', "%{$search}%")
                                    ->orWhere('name_en', 'like', "%{$search}%");
                             });
                      });
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
                // إضافة اسم الوكالة أو اسم الموظف للادمن
                if ($isAdmin) {
                    if ($document->branchAgent && !empty($document->branchAgent->agency_name)) {
                        $document->agency_name = $document->branchAgent->agency_name;
                        $document->user_name = null;
                        $document->is_agency = true;
                    } else {
                        $userName = $document->user ? ($document->user->name ?? $document->user->username) : null;
                        $document->agency_name = $userName;
                        $document->user_name = $userName;
                        $document->is_agency = false;
                    }
                } else {
                    $document->agency_name = null;
                    $document->user_name = null;
                    $document->is_agency = false;
                }
                return $document;
            });

            return response()->json($documents);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@index: ' . $e->getMessage());
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
                'insurance_type' => 'required|in:تأمين المسافرين,تأمين زائرين ليبيا',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'nullable|string|max:255',
                'geographic_area' => 'nullable|string|max:255',
                'residence_type' => 'nullable|string|max:255|sometimes|required_if:insurance_type,تأمين زائرين ليبيا',
                'residence_duration' => 'nullable|integer|min:0|sometimes|required_if:insurance_type,تأمين زائرين ليبيا',
                'premium' => 'required|numeric|min:0',
                'family_members_premium' => 'nullable|numeric|min:0',
                'stamp' => 'nullable|numeric|min:0',
                'issue_fees' => 'nullable|numeric|min:0',
                'supervision_fees' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'passengers' => 'required|array|min:1',
                'passengers.*.is_main_passenger' => 'required|boolean',
                'passengers.*.name_ar' => 'required|string|max:255',
                'passengers.*.name_en' => 'required|string|max:255',
                'passengers.*.phone' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.whatsapp_number' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.passport_number' => 'required|string|max:255',
                'passengers.*.address' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string',
                'passengers.*.birth_date' => 'required|date',
                'passengers.*.age' => 'nullable|integer|min:0|max:150',
                'passengers.*.gender' => 'required|in:ذكر,أنثى',
                'passengers.*.nationality' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.relationship' => 'required_if:passengers.*.is_main_passenger,false,0|nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي
            $lastDocument = TravelInsuranceDocument::where('insurance_number', 'like', 'BKTRV%')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastDocument && preg_match('/BKTRV(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            do {
                $insuranceNumber = 'BKTRV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (TravelInsuranceDocument::where('insurance_number', $insuranceNumber)->exists());

            // الحصول على branch_agent_id من المستخدم الحالي
            $branchAgentId = null;
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int) $userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user && !($user->is_admin ?? false)) {
                        // إذا لم يكن admin، احصل على branch_agent_id من المستخدم أو الموظف التابع له
                            $branchAgentId = $user->branch_agent_id;
                            if (!$branchAgentId) {
                                $branchAgent = BranchAgent::where('user_id', $userId)->first();
                                if ($branchAgent) {
                                    $branchAgentId = $branchAgent->id;
                                }
                            }
                    }
                }
            }

            // إنشاء الوثيقة
            $document = TravelInsuranceDocument::create([
                'insurance_type' => $validated['insurance_type'],
                'insurance_number' => $insuranceNumber,
                'issue_date' => Carbon::now(),
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'duration' => $validated['duration'] ?? null,
                'geographic_area' => $validated['geographic_area'] ?? null,
                'residence_type' => $validated['residence_type'] ?? null,
                'residence_duration' => $validated['residence_duration'] ?? null,
                'premium' => $validated['premium'],
                'family_members_premium' => $validated['family_members_premium'] ?? 0,
                'stamp' => $validated['stamp'] ?? 0.500,
                'issue_fees' => $validated['issue_fees'] ?? 0,
                'supervision_fees' => $validated['supervision_fees'] ?? 0.180,
                'total' => $validated['total'],
                'branch_agent_id' => $branchAgentId,
                'user_id' => $userId,
            ]);

            // إنشاء المسافرين
            foreach ($validated['passengers'] as $passengerData) {
                TravelInsurancePassenger::create([
                    'travel_insurance_document_id' => $document->id,
                    'is_main_passenger' => $passengerData['is_main_passenger'],
                    'relationship' => $passengerData['relationship'] ?? null,
                    'name_ar' => $passengerData['name_ar'],
                    'name_en' => $passengerData['name_en'],
                    'phone' => $passengerData['phone'] ?? null,
                    'whatsapp_number' => $passengerData['whatsapp_number'] ?? null,
                    'passport_number' => $passengerData['passport_number'] ?? null,
                    'address' => $passengerData['address'] ?? null,
                    'birth_date' => $passengerData['birth_date'] ?? null,
                    'age' => $passengerData['age'] ?? null,
                    'gender' => $passengerData['gender'],
                    'nationality' => $passengerData['nationality'] ?? null,
                ]);
            }

            return response()->json($document->load('passengers'), 201);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@store: ' . $e->getMessage());
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
            $document = TravelInsuranceDocument::with('passengers')->findOrFail($id);
            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@show: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الوثيقة',
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
                'insurance_type' => 'required|in:تأمين المسافرين,تأمين زائرين ليبيا',
                'start_date' => 'required|date',
                'end_date' => 'nullable|date',
                'duration' => 'nullable|string|max:255',
                'geographic_area' => 'nullable|string|max:255',
                'residence_type' => 'nullable|string|max:255|sometimes|required_if:insurance_type,تأمين زائرين ليبيا',
                'residence_duration' => 'nullable|integer|min:0|sometimes|required_if:insurance_type,تأمين زائرين ليبيا',
                'premium' => 'required|numeric|min:0',
                'family_members_premium' => 'nullable|numeric|min:0',
                'stamp' => 'nullable|numeric|min:0',
                'issue_fees' => 'nullable|numeric|min:0',
                'supervision_fees' => 'nullable|numeric|min:0',
                'total' => 'required|numeric|min:0',
                'passengers' => 'required|array|min:1',
                'passengers.*.is_main_passenger' => 'required|boolean',
                'passengers.*.name_ar' => 'required|string|max:255',
                'passengers.*.name_en' => 'required|string|max:255',
                'passengers.*.phone' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.whatsapp_number' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.passport_number' => 'required|string|max:255',
                'passengers.*.address' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string',
                'passengers.*.birth_date' => 'required|date',
                'passengers.*.age' => 'nullable|integer|min:0|max:150',
                'passengers.*.gender' => 'required|in:ذكر,أنثى',
                'passengers.*.nationality' => 'required_if:passengers.*.is_main_passenger,true,1|nullable|string|max:255',
                'passengers.*.relationship' => 'required_if:passengers.*.is_main_passenger,false,0|nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $document = TravelInsuranceDocument::with('passengers')->findOrFail($id);

            // تحديث branch_agent_id فقط إذا كان المستخدم admin أو إذا لم يكن للوثيقة branch_agent_id
            $branchAgentId = $document->branch_agent_id; // الحفاظ على القيمة الحالية
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int) $userId : null;
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
            }

            // تحديث بيانات الوثيقة
            $document->update([
                'insurance_type' => $validated['insurance_type'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'duration' => $validated['duration'] ?? null,
                'geographic_area' => $validated['geographic_area'] ?? null,
                'residence_type' => $validated['residence_type'] ?? null,
                'residence_duration' => $validated['residence_duration'] ?? null,
                'premium' => $validated['premium'],
                'family_members_premium' => $validated['family_members_premium'] ?? 0,
                'stamp' => $validated['stamp'] ?? 0.500,
                'issue_fees' => $validated['issue_fees'] ?? 0,
                'supervision_fees' => $validated['supervision_fees'] ?? 0.180,
                'total' => $validated['total'],
                'branch_agent_id' => $branchAgentId,
                'user_id' => $userId,
            ]);

            // حذف المسافرين الحاليين وإعادة إنشائهم
            $document->passengers()->delete();

            // إنشاء المسافرين الجدد
            foreach ($validated['passengers'] as $passengerData) {
                TravelInsurancePassenger::create([
                    'travel_insurance_document_id' => $document->id,
                    'is_main_passenger' => $passengerData['is_main_passenger'],
                    'relationship' => $passengerData['relationship'] ?? null,
                    'name_ar' => $passengerData['name_ar'],
                    'name_en' => $passengerData['name_en'],
                    'phone' => $passengerData['phone'] ?? null,
                    'whatsapp_number' => $passengerData['whatsapp_number'] ?? null,
                    'passport_number' => $passengerData['passport_number'] ?? null,
                    'address' => $passengerData['address'] ?? null,
                    'birth_date' => $passengerData['birth_date'] ?? null,
                    'age' => $passengerData['age'] ?? null,
                    'gender' => $passengerData['gender'],
                    'nationality' => $passengerData['nationality'] ?? null,
                ]);
            }

            return response()->json($document->load('passengers'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث الوثيقة',
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
            $document = TravelInsuranceDocument::findOrFail($id);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print travel insurance document
     */
    public function print(string $id)
    {
        try {
            $document = TravelInsuranceDocument::with(['passengers', 'branchAgent'])->findOrFail($id);

            // تحضير بيانات الوكالة
            $agencyData = [
                'agency_name' => 'المدار الليبي للتأمين',
                'code' => 'ML0001',
                'agent_name' => 'الإدارة',
            ];

            if ($document->branchAgent) {
                $agencyData['agency_name'] = $document->branchAgent->agency_name ?? 'المدار الليبي للتأمين';
                $agencyData['code'] = $document->branchAgent->code ?? 'ML0001';
                $agencyData['agent_name'] = $document->branchAgent->agent_name ?? 'الإدارة';
            }

            // تحضير البيانات للطباعة
            $mainPassenger = $document->passengers->where('is_main_passenger', true)->first();

            $printData = [
                'insurance_number' => $document->insurance_number,
                'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'start_date' => \Carbon\Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => \Carbon\Carbon::parse($document->end_date)->format('d/m/Y'),
                'duration' => $document->duration,
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'qr_data' => [
                    'insurance_number' => $document->insurance_number,
                    'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $mainPassenger ? $mainPassenger->name_ar : '',
                    'total' => $document->total
                ]
            ];

            return view('travel-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in TravelInsuranceDocumentController@print: ' . $e->getMessage());
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
                $words .= $hundreds[$hundred] . ' ';
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

    /**
     * إلغاء وثيقة - Soft Cancel (مخصص للأدمن والموظفين المصرح لهم فقط - يمنع الوكلاء تماماً)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if (!$userId) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }
            $userId = is_numeric($userId) ? (int) $userId : null;
            $user = $userId ? \App\Models\User::find($userId) : null;
            if (!$user) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }

            // منع الوكلاء من الإلغاء تماماً
            if ($user->branch_agent_id || \App\Models\BranchAgent::where('user_id', $user->id)->exists()) {
                return response()->json(['message' => 'الوكلاء غير مصرح لهم بإلغاء الوثائق'], 403);
            }

            // فقط الأدمن والموظفين المصرح لهم
            $isAdmin = $user->is_admin ?? false;
            $authDocs = is_array($user->authorized_documents)
                ? $user->authorized_documents
                : (is_string($user->authorized_documents) ? json_decode($user->authorized_documents, true) : []);
            if (!is_array($authDocs)) $authDocs = [];

            $hasPermission = $isAdmin || !empty($authDocs) || ($user->department_id !== null);
            if (!$hasPermission) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }

            $validated = $request->validate(['cancel_reason' => 'required|string|max:1000']);
            
            $modelMap = [
                'InsuranceDocumentController' => \App\Models\InsuranceDocument::class,
                'TravelInsuranceDocumentController' => \App\Models\TravelInsuranceDocument::class,
                'ResidentInsuranceDocumentController' => \App\Models\ResidentInsuranceDocument::class,
                'MarineStructureInsuranceDocumentController' => \App\Models\MarineStructureInsuranceDocument::class,
                'ProfessionalLiabilityInsuranceDocumentController' => \App\Models\ProfessionalLiabilityInsuranceDocument::class,
                'PersonalAccidentInsuranceDocumentController' => \App\Models\PersonalAccidentInsuranceDocument::class,
                'SchoolStudentInsuranceDocumentController' => \App\Models\SchoolStudentInsuranceDocument::class,
                'CashInTransitInsuranceDocumentController' => \App\Models\CashInTransitInsuranceDocument::class,
                'CargoInsuranceDocumentController' => \App\Models\CargoInsuranceDocument::class,
                'InternationalInsuranceDocumentController' => \App\Models\InternationalInsuranceDocument::class,
            ];

            $shortName = (new \ReflectionClass($this))->getShortName();
            $modelClass = $modelMap[$shortName] ?? null;

            if (!$modelClass) {
                return response()->json(['message' => 'تعذر تحديد نوع الوثيقة'], 500);
            }

            $document = $modelClass::findOrFail($id);
            if ($document->is_canceled) {
                return response()->json(['message' => 'هذه الوثيقة ملغية بالفعل'], 422);
            }

            $document->update([
                'is_canceled' => true,
                'canceled_at' => now(),
                'canceled_by' => $userId,
                'cancel_reason' => $validated['cancel_reason'],
            ]);

            return response()->json(['message' => 'تم إلغاء الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'سبب الإلغاء مطلوب', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in cancel document: " . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إلغاء الوثيقة'], 500);
        }
    }
}
