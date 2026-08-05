<?php

namespace App\Http\Controllers;

use App\Models\InternationalInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class InternationalInsuranceDocumentController extends Controller
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
                            // إذا لم يكن admin، احصل على branch_agent_id من المستخدم (سواء كان مستخدم مكتب فرعي أو وكيل رئيسي)
                            if ($user->branch_agent_id) {
                                $branchAgentId = $user->branch_agent_id;
                            } else {
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
            $query = InternationalInsuranceDocument::with(['vehicleType', 'branchAgent']);
            
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
                    $q->where('document_number', 'like', "%{$search}%")
                      ->orWhere('insured_name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
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
            Log::error('Error in InternationalInsuranceDocumentController@index: ' . $e->getMessage());
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
                'external_policy_number' => 'nullable|string|max:255',
                'insured_name' => 'required|string|max:255',
                'insured_address' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'whatsapp_number' => 'required|string|max:255',
                'chassis_number' => 'required|string|max:255',
                'plate_number' => 'required|string|max:255',
                'vehicle_type_id' => 'required_without:external_car_id|nullable|exists:vehicle_types,id',
                'external_car_id' => 'required_without:vehicle_type_id|nullable|integer',
                'external_vehicle_nationality_id' => 'nullable|integer',
                'external_country_id' => 'nullable|integer',
                'year' => 'required|integer|min:1960|max:2026',
                'vehicle_nationality' => 'required|string|max:255',
                'visited_country' => 'required|string|max:255',
                'start_date' => 'required|date',
                'number_of_days' => 'required|integer|min:1',
                'end_date' => 'required|date',
                'item_type' => 'required|in:سيارات خاصة ملاكي,دراجة نارية,سيارة تعليم قيادة,سيارة اسعاف,سيارة نقل الموتى,مقطورة,السيارات التجارية,الجرارات,سيارات نقل بضائع,سيارات الركوبة الحافلات',
                'number_of_countries' => 'nullable|integer|min:1',
                'daily_premium' => 'nullable|numeric|min:0',
                'premium' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'supervision_fees' => 'nullable|numeric|min:0',
                'issue_fees' => 'nullable|numeric|min:0',
                'stamp' => 'nullable|numeric|min:0',
                'total' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            // توليد رقم الوثيقة LBY0001
            $lastDocument = InternationalInsuranceDocument::where('document_number', 'like', 'LBY%')
                ->orderBy('id', 'desc')
                ->first();
            if ($lastDocument && preg_match('/LBY(\d+)/', $lastDocument->document_number, $matches)) {
                $nextNumber = (int)$matches[1] + 1;
            } else {
                $nextNumber = 1;
            }
            do {
                $documentNumber = 'LBY' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (InternationalInsuranceDocument::where('document_number', $documentNumber)->exists());

            // الحصول على branch_agent_id والتحقق من الصلاحيات للمستخدم الحالي
            $branchAgentId = null;
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $userId = is_numeric($userId) ? (int)$userId : null;
                if ($userId) {
                    $user = User::find($userId);
                    if ($user && !($user->is_admin ?? false)) {
                        // إذا لم يكن admin، احصل على branch_agent_id من المستخدم (سواء كان مستخدم مكتب فرعي أو وكيل رئيسي)
                        if ($user->branch_agent_id) {
                            $branchAgentId = $user->branch_agent_id;
                            // التحقق من صلاحية إصدار الوثائق لمستخدمي المكتب الفرعيين (صلاحية رقم 2)
                            $isSubUser = !empty($user->lifo_user_id) || (!empty($user->lifo_permissions) && count($user->lifo_permissions) > 0);
                            if ($isSubUser) {
                                $permissions = $user->lifo_permissions ?? [];
                                if (!in_array(2, $permissions)) {
                                    return response()->json([
                                        'message' => 'ليس لديك صلاحية لإصدار وثائق التأمين الدولي. يرجى مراجعة إدارة المكتب.'
                                    ], 403);
                                }
                            }
                        } else {
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            if ($branchAgent) {
                                $branchAgentId = $branchAgent->id;
                            }
                        }
                    }
                }
            }

            $document = InternationalInsuranceDocument::create([
                'document_number' => $documentNumber,
                'external_policy_number' => $validated['external_policy_number'] ?? null,
                'insured_name' => $validated['insured_name'],
                'insured_address' => $validated['insured_address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'],
                'chassis_number' => $validated['chassis_number'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'vehicle_type_id' => $validated['vehicle_type_id'] ?? null,
                'external_car_id' => $validated['external_car_id'] ?? null,
                'external_vehicle_nationality_id' => $validated['external_vehicle_nationality_id'] ?? null,
                'external_country_id' => $validated['external_country_id'] ?? null,
                'year' => $validated['year'] ?? null,
                'vehicle_nationality' => $validated['vehicle_nationality'],
                'visited_country' => $validated['visited_country'] ?? null,
                'start_date' => $validated['start_date'],
                'number_of_days' => $validated['number_of_days'],
                'end_date' => $validated['end_date'],
                'item_type' => $validated['item_type'],
                'number_of_countries' => $validated['number_of_countries'] ?? 1,
                'daily_premium' => $validated['daily_premium'] ?? 0,
                'premium' => $validated['premium'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'supervision_fees' => $validated['supervision_fees'] ?? 0,
                'issue_fees' => $validated['issue_fees'] ?? 10.000,
                'stamp' => $validated['stamp'] ?? 0.250,
                'total' => $validated['total'] ?? 0,
                'issue_date' => now(),
                'branch_agent_id' => $branchAgentId,
                'user_id' => $userId,
            ]);

            return response()->json($document->load('vehicleType'), 201);
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@store: ' . $e->getMessage());
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
            $document = InternationalInsuranceDocument::with('vehicleType')->findOrFail($id);
            return response()->json($document);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@show: ' . $e->getMessage());
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
                'external_policy_number' => 'nullable|string|max:255',
                'insured_name' => 'required|string|max:255',
                'insured_address' => 'required|string|max:255',
                'phone' => 'required|string|max:255',
                'whatsapp_number' => 'required|string|max:255',
                'chassis_number' => 'required|string|max:255',
                'plate_number' => 'required|string|max:255',
                'vehicle_type_id' => 'required_without:external_car_id|nullable|exists:vehicle_types,id',
                'external_car_id' => 'required_without:vehicle_type_id|nullable|integer',
                'external_vehicle_nationality_id' => 'nullable|integer',
                'external_country_id' => 'nullable|integer',
                'year' => 'required|integer|min:1960|max:2026',
                'vehicle_nationality' => 'required|string|max:255',
                'visited_country' => 'required|string|max:255',
                'start_date' => 'required|date',
                'number_of_days' => 'required|integer|min:1',
                'end_date' => 'required|date',
                'item_type' => 'required|in:سيارات خاصة ملاكي,دراجة نارية,سيارة تعليم قيادة,سيارة اسعاف,سيارة نقل الموتى,مقطورة,السيارات التجارية,الجرارات,سيارات نقل بضائع,سيارات الركوبة الحافلات',
                'number_of_countries' => 'nullable|integer|min:1',
                'daily_premium' => 'nullable|numeric|min:0',
                'premium' => 'nullable|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'supervision_fees' => 'nullable|numeric|min:0',
                'issue_fees' => 'nullable|numeric|min:0',
                'stamp' => 'nullable|numeric|min:0',
                'total' => 'nullable|numeric|min:0',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $document = InternationalInsuranceDocument::findOrFail($id);

            $document->update([
                'external_policy_number' => $validated['external_policy_number'] ?? $document->external_policy_number,
                'insured_name' => $validated['insured_name'],
                'insured_address' => $validated['insured_address'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'whatsapp_number' => $validated['whatsapp_number'],
                'chassis_number' => $validated['chassis_number'] ?? null,
                'plate_number' => $validated['plate_number'] ?? null,
                'vehicle_type_id' => $validated['vehicle_type_id'] ?? null,
                'external_car_id' => $validated['external_car_id'] ?? null,
                'external_vehicle_nationality_id' => $validated['external_vehicle_nationality_id'] ?? null,
                'external_country_id' => $validated['external_country_id'] ?? null,
                'year' => $validated['year'] ?? null,
                'vehicle_nationality' => $validated['vehicle_nationality'],
                'visited_country' => $validated['visited_country'] ?? null,
                'start_date' => $validated['start_date'],
                'number_of_days' => $validated['number_of_days'],
                'end_date' => $validated['end_date'],
                'item_type' => $validated['item_type'],
                'number_of_countries' => $validated['number_of_countries'] ?? 1,
                'daily_premium' => $validated['daily_premium'] ?? 0,
                'premium' => $validated['premium'] ?? 0,
                'tax' => $validated['tax'] ?? 0,
                'supervision_fees' => $validated['supervision_fees'] ?? 0,
                'issue_fees' => $validated['issue_fees'] ?? 10.000,
                'stamp' => $validated['stamp'] ?? 0.250,
                'total' => $validated['total'] ?? 0,
            ]);

            return response()->json($document->load('vehicleType'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@update: ' . $e->getMessage());
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
            $document = InternationalInsuranceDocument::findOrFail($id);
            $document->delete();
            return response()->json(['message' => 'تم حذف الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'الوثيقة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Print international insurance document
     */
    public function print(string $id)
    {
        try {
            $document = InternationalInsuranceDocument::with(['vehicleType', 'branchAgent'])->findOrFail($id);
            
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
            $printData = [
                'document_number' => $document->document_number,
                'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('d/m/Y h:i A'),
                'start_date' => \Carbon\Carbon::parse($document->start_date)->format('d/m/Y'),
                'end_date' => \Carbon\Carbon::parse($document->end_date)->format('d/m/Y'),
                'number_of_days' => $document->number_of_days,
                'total_in_words' => $this->numberToArabicWords($document->total),
                'agency_name' => $agencyData['agency_name'],
                'agency_code' => $agencyData['code'],
                'agent_name' => $agencyData['agent_name'],
                'qr_data' => [
                    'document_number' => $document->document_number,
                    'issue_date' => \Carbon\Carbon::parse($document->issue_date)->format('Y-m-d'),
                    'insured_name' => $document->insured_name ?? '',
                    'total' => $document->total
                ]
            ];
            
            return view('international-insurance-documents.print', compact('document', 'printData'));
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@print: ' . $e->getMessage());
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

    /**
     * Manual trigger for synchronizing documents with Union (LIFO).
     */
    public function syncFromUnion(Request $request)
    {
        try {
            // Check if a sync is already running
            $currentStatus = Cache::get('union_sync_status');
            if ($currentStatus && ($currentStatus['status'] ?? '') === 'running') {
                return response()->json([
                    'success' => false,
                    'message' => 'عملية المزامنة قيد التشغيل حالياً، يرجى الانتظار لحين اكتمالها.',
                    'already_running' => true,
                ]);
            }

            // Mark as running immediately so UI updates
            Cache::put('union_sync_status', [
                'status'  => 'running',
                'message' => 'جاري بدء المزامنة مع الاتحاد...',
                'started_at' => now()->toDateTimeString(),
            ], 1800);

            // Run the artisan command as a background process (no queue worker needed)
            $artisanPath = base_path('artisan');
            $phpBinary = PHP_BINARY ?: 'php';
            $command = "{$phpBinary} {$artisanPath} lifo:sync-documents > /dev/null 2>&1 &";
            pclose(popen($command, 'r'));

            return response()->json([
                'success' => true,
                'message' => 'تم بدء المزامنة في الخلفية، يمكنك متابعة حالتها.',
                'background' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in InternationalInsuranceDocumentController@syncFromUnion: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل بدء المزامنة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check the status of the background sync job.
     */
    public function syncStatus()
    {
        $status = Cache::get('union_sync_status');

        if (!$status) {
            return response()->json([
                'status'  => 'idle',
                'message' => 'لا توجد عملية مزامنة حالياً',
            ]);
        }

        return response()->json($status);
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
