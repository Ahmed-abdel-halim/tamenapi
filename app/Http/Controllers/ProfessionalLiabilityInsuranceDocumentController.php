<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalLiabilityInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProfessionalLiabilityInsuranceDocumentController extends Controller
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
            $query = ProfessionalLiabilityInsuranceDocument::with('branchAgent');
            if ($request->boolean('archived')) {
                $query->archived();
            } else {
                $query->active();
            }

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
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف',
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
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
                'contract_relation' => 'required|in:نفسه,جهة العمل',
                'contractor_name' => 'nullable|string|max:255',
                'insured_name' => 'required|string|max:255',
                'birth_date' => 'nullable|date',
                'age' => 'nullable|integer|min:0|max:150',
                'phone' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',
                'gender' => 'nullable|in:ذكر Male,انثى Female',
                'nationality' => 'nullable|string|max:255',
                'profession' => 'nullable|string|max:255',
                'marital_status' => 'nullable|in:أعزب/عزباء,متزوج/متزوجة,مطلق/مطلقة',
                'premium' => 'required|numeric|min:0',
                'tax' => 'required|numeric|min:0',
                'stamp' => 'required|numeric|min:0',
                'issue_fees' => 'required|numeric|min:0',
                'supervision_fees' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم التأمين التلقائي BKPRL00001
            $lastDocument = ProfessionalLiabilityInsuranceDocument::orderBy('id', 'desc')->first();
            if ($lastDocument && preg_match('/BKPRL(\d+)/', $lastDocument->insurance_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            $insuranceNumber = 'BKPRL' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

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

            // حساب start_date = issue_date و end_date = issue_date + سنة
            $issueDate = Carbon::now();
            $startDate = $issueDate->copy()->startOfDay();
            $endDate = $issueDate->copy()->addYear()->startOfDay();

            $document = ProfessionalLiabilityInsuranceDocument::create([
                'insurance_number' => $insuranceNumber,
                'issue_date' => $issueDate,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration' => 'سنة (365 يوم)',
                'contract_relation' => $validated['contract_relation'],
                'contractor_name' => $validated['contractor_name'] ?? null,
                'insured_name' => $validated['insured_name'],
                'birth_date' => $validated['birth_date'] ?? null,
                'age' => $validated['age'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'workplace' => $validated['workplace'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'nationality' => $validated['nationality'] ?? null,
                'profession' => $validated['profession'] ?? null,
                'marital_status' => $validated['marital_status'] ?? null,
                'premium' => $validated['premium'],
                'tax' => $validated['tax'],
                'stamp' => $validated['stamp'],
                'issue_fees' => $validated['issue_fees'],
                'supervision_fees' => $validated['supervision_fees'],
                'total' => $validated['total'],
                'branch_agent_id' => $branchAgentId,
            ]);

            return response()->json($document, 201);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@store: ' . $e->getMessage());
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
            $document = ProfessionalLiabilityInsuranceDocument::findOrFail($document);
            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@show: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
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
            $document = ProfessionalLiabilityInsuranceDocument::findOrFail($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'contract_relation' => 'required|in:نفسه,جهة العمل',
                'contractor_name' => 'nullable|string|max:255',
                'insured_name' => 'required|string|max:255',
                'birth_date' => 'nullable|date',
                'age' => 'nullable|integer|min:0|max:150',
                'phone' => 'nullable|string|max:255',
                'workplace' => 'nullable|string|max:255',
                'gender' => 'nullable|in:ذكر Male,انثى Female',
                'nationality' => 'nullable|string|max:255',
                'profession' => 'nullable|string|max:255',
                'marital_status' => 'nullable|in:أعزب/عزباء,متزوج/متزوجة,مطلق/مطلقة',
                'premium' => 'required|numeric|min:0',
                'tax' => 'required|numeric|min:0',
                'stamp' => 'required|numeric|min:0',
                'issue_fees' => 'required|numeric|min:0',
                'supervision_fees' => 'required|numeric|min:0',
                'total' => 'required|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
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

            // حساب start_date = issue_date و end_date = issue_date + سنة (لا يمكن تعديلها)
            $issueDate = Carbon::parse($document->issue_date);
            $startDate = $issueDate->copy()->startOfDay();
            $endDate = $issueDate->copy()->addYear()->startOfDay();
            
            $validated['branch_agent_id'] = $branchAgentId;
            $validated['start_date'] = $startDate;
            $validated['end_date'] = $endDate;
            $validated['duration'] = 'سنة (365 يوم)';
            $document->update($validated);
            return response()->json($document);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@update: ' . $e->getMessage());
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
            $document = ProfessionalLiabilityInsuranceDocument::findOrFail($document);
            $document->delete();
            return response()->json(['status' => 'deleted']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print the specified document.
     */
    public function print($document)
    {
        try {
            $document = ProfessionalLiabilityInsuranceDocument::with('branchAgent')->findOrFail($document);
            
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
            
            // تحضير البيانات للطباعة
            $printData = [
                'issue_date' => Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'insurance_number' => $document->insurance_number,
                'start_date' => Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => Carbon::parse($document->end_date)->format('d/m/Y'),
                'duration' => $document->duration === 'سنة (365 يوم)' ? '365 يوم' : $document->duration,
                'insured_name' => $document->insured_name ?? '-',
                'phone' => $document->phone ?? '-',
                'contractor_name' => $document->contractor_name ?? '-',
                'contract_relation' => $document->contract_relation ?? '-',
                'workplace' => $document->workplace ?? '-',
                'gender' => $document->gender ?? '-',
                'birth_date' => $document->birth_date ? Carbon::parse($document->birth_date)->format('d/m/Y') : '-',
                'nationality' => $document->nationality ?? '-',
                'profession' => $document->profession ?? '-',
                'marital_status' => $document->marital_status ?? '-',
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
            
            return view('professional-liability-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in ProfessionalLiabilityInsuranceDocumentController@print: ' . $e->getMessage());
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

