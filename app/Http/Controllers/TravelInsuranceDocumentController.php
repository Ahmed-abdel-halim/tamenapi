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
            $query = TravelInsuranceDocument::with(['passengers', 'branchAgent']);

            // إذا لم يكن admin، قم بتصفية الوثائق حسب branch_agent_id
            if (!$isAdmin && $branchAgentId) {
                $query->where('branch_agent_id', $branchAgentId);
            }

            $documents = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($document) use ($isAdmin) {
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
                'passengers.*.phone' => 'nullable|string|max:255',
                'passengers.*.passport_number' => 'nullable|string|max:255',
                'passengers.*.address' => 'nullable|string',
                'passengers.*.birth_date' => 'nullable|date',
                'passengers.*.age' => 'nullable|integer|min:0|max:150',
                'passengers.*.gender' => 'required|in:ذكر,أنثى',
                'passengers.*.nationality' => 'nullable|string|max:255',
                'passengers.*.relationship' => 'nullable|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي
            $lastDocument = TravelInsuranceDocument::orderBy('id', 'desc')->first();
            if ($lastDocument && preg_match('/BKTRV(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            $insuranceNumber = 'BKTRV' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

            // الحصول على branch_agent_id من المستخدم الحالي
            $branchAgentId = null;
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user && !($user->is_admin ?? false)) {
                        // إذا لم يكن admin، احصل على branch_agent_id من المستخدم
                        $branchAgent = BranchAgent::where('user_id', $userId)->first();
                        if ($branchAgent) {
                            $branchAgentId = $branchAgent->id;
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
                'passengers.*.phone' => 'nullable|string|max:255',
                'passengers.*.passport_number' => 'nullable|string|max:255',
                'passengers.*.address' => 'nullable|string',
                'passengers.*.birth_date' => 'nullable|date',
                'passengers.*.age' => 'nullable|integer|min:0|max:150',
                'passengers.*.gender' => 'required|in:ذكر,أنثى',
                'passengers.*.nationality' => 'nullable|string|max:255',
                'passengers.*.relationship' => 'nullable|string|max:255',
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
                $userId = is_numeric($userId) ? (int)$userId : null;
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
