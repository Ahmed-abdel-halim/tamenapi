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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ExcelImportController extends Controller
{
    /**
     * الخطوة 1: تحليل الملف وإرجاع نتائج المطابقة بدون حفظ
     * Analyze Excel file and return agent matching results
     */
    private array $matchCache = [];
    private static bool $firstRowLogged = false;

    public function analyzeFile(Request $request)
    {
        set_time_limit(300); // زيادة وقت التنفيذ للملفات الكبيرة

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv',
                'import_type' => 'required|in:insurance,travel,resident,marine,professional,personal,international',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $file = $request->file('file');
            $importType = $request->input('import_type');
            $rows = $this->readExcelFile($file->getPathname(), $file->getClientOriginalExtension());

            if (empty($rows)) {
                return response()->json(['message' => 'الملف فارغ أو لا يمكن قراءته'], 422);
            }

            // جلب جميع الوكلاء من قاعدة البيانات
            $agents = BranchAgent::select('id', 'agent_name', 'agency_name', 'code', 'status')->get();

            // البحث عن صف الهيدر الحقيقي (لأن بعض الملفات تحتوي على ترويسة وعناوين قبل الجدول)
            $headerRowIndex = 0;
            foreach ($rows as $idx => $r) {
                if ($idx > 20)
                    break; // البحث في أول 20 صف فقط
                $rowStr = implode(' ', array_map(fn($v) => (string) $v, $r));
                if (str_contains($rowStr, 'رقم') || str_contains($rowStr, 'اسم') || str_contains($rowStr, 'تاريخ') || str_contains($rowStr, 'قسط')) {
                    $headerRowIndex = $idx;
                    break;
                }
            }

            $headers = [];
            if (!empty($rows[$headerRowIndex])) {
                $headers = array_map(fn($h) => trim((string) $h), $rows[$headerRowIndex]);
            }

            $results = [];
            foreach ($rows as $index => $row) {
                if ($index <= $headerRowIndex)
                    continue; // تخطي الهيدر وكل ما قبله

                // إضافة المفاتيح النصية من الهيدر مع تطبيع المسافات لضمان التطابق
                $fullRow = $row;
                foreach ($headers as $colIdx => $colName) {
                    if ($colName !== '') {
                        // تخزين باسم العمود الأصلي + نسخة منظمة (بمسافة واحدة) لتسهيل البحث
                        $normalizedColName = trim(preg_replace('/\s+/u', ' ', $colName));
                        $fullRow[$colName] = $row[$colIdx] ?? null;
                        $fullRow[$normalizedColName] = $row[$colIdx] ?? null;
                    }
                }

                // إضافة بعض المرادفات للبحث عن الوكيل (استخدام كلمات مفتاحية للبحث الجزئي)
                $agentNameInFile = $this->extractValue($fullRow, ['وكيل', 'مستخدم', 'وسيط', 'agent', 'user']) ?? '';
                $agencyNameInFile = $this->extractValue($fullRow, ['وكال', 'جهة الإصدار', 'جهة الاصدار', 'agency']) ?? '';

                // إذا كان الصف فارغاً تماماً من البيانات المهمة
                if (empty(implode('', $row))) {
                    continue;
                }

                $matchResult = $this->findBestMatch($agentNameInFile, $agencyNameInFile, $agents);

                $results[] = [
                    'row_index' => $index,
                    'raw_data' => $fullRow,
                    'agent_name_in_file' => $agentNameInFile,
                    'agency_name_in_file' => $agencyNameInFile,
                    'match_status' => $matchResult['status'],    // exact | fuzzy | not_found
                    'match_score' => $matchResult['score'],
                    'suggested_agent' => $matchResult['agent'],
                    'all_candidates' => $matchResult['candidates'], // أفضل 3 مرشحين
                    'selected_agent_id' => $matchResult['agent'] ? $matchResult['agent']['id'] : null,
                    'action' => $matchResult['status'] === 'exact' ? 'link' : ($matchResult['status'] === 'fuzzy' ? 'review' : 'create'),
                ];
            }

            return response()->json([
                'success' => true,
                'total_rows' => count($results),
                'exact_count' => collect($results)->where('match_status', 'exact')->count(),
                'fuzzy_count' => collect($results)->where('match_status', 'fuzzy')->count(),
                'new_count' => collect($results)->where('match_status', 'not_found')->count(),
                'results' => $results,
                'headers' => !empty($rows[0]) ? array_values($rows[0]) : [],
            ]);
        } catch (\Exception $e) {
            Log::error('ExcelImportController@analyzeFile: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحليل الملف: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * الخطوة 2: تأكيد الاستيراد بعد مراجعة المستخدم
     * Confirm import after user review
     */
    public function confirmImport(Request $request)
    {
        try {
            $request->validate([
                'import_type' => 'required|in:insurance,travel,resident,marine,professional,personal,international',
                'rows' => 'required|array|min:1',
                'rows.*.raw_data' => 'required|array',
                'rows.*.selected_agent_id' => 'nullable|integer|exists:branches_agents,id',
                'rows.*.action' => 'required|in:link,create,create_agent,skip',
                'rows.*.agent_name_in_file' => 'nullable|string',
                'rows.*.agency_name_in_file' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق',
                'errors' => $e->errors()
            ], 422);
        }

        // إيقاف حدود الوقت لمعالجة آلاف السجلات
        set_time_limit(0);
        ini_set('memory_limit', '1G');

        $importType = $request->input('import_type');
        $rows = $request->input('rows');

        $imported = 0;
        $skipped = 0;
        $agentsCreated = 0;
        $errors = [];

        // Cache لتجنب إنشاء نفس الوكيل مرتين في نفس الاستيراد
        // key = "agent_name|agency_name" => branch_agent_id
        $createdAgentsCache = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                if ($row['action'] === 'skip') {
                    $skipped++;
                    continue;
                }

                $agentId = $row['selected_agent_id'] ?? null;

                // إنشاء وكيل جديد تلقائياً من بيانات الملف
                if ($row['action'] === 'create_agent') {
                    $agentName = trim($row['agent_name_in_file'] ?? '');
                    $agencyName = trim($row['agency_name_in_file'] ?? '');
                    $cacheKey = mb_strtolower($agentName . '|' . $agencyName);

                    if (!empty($agentName) || !empty($agencyName)) {
                        if (isset($createdAgentsCache[$cacheKey])) {
                            // استخدم الوكيل المُنشأ مسبقاً في نفس الدُفعة
                            $agentId = $createdAgentsCache[$cacheKey];
                        } else {
                            try {
                                // التحقق مما إذا كان الوكيل قد تم إنشاؤه للتو في دفعة سابقة
                                $searchName = $agentName ?: $agencyName;
                                $existingAgent = BranchAgent::where('agent_name', $searchName)
                                    ->orWhere('agency_name', $searchName)
                                    ->first();

                                if ($existingAgent) {
                                    $agentId = $existingAgent->id;
                                    $createdAgentsCache[$cacheKey] = $agentId;
                                } else {
                                    $newAgent = $this->createAgentFromRow(
                                        $agentName ?: 'غير محدد',
                                        $agentName ?: 'غير محدد'
                                    );
                                    $agentId = $newAgent->id;
                                    $createdAgentsCache[$cacheKey] = $agentId;
                                    $agentsCreated++;
                                }
                            } catch (\Exception $e) {
                                Log::warning('ExcelImport: failed to create agent for row ' . ($index + 1) . ': ' . $e->getMessage());
                                // نكمل الاستيراد بدون وكيل
                                $agentId = null;
                            }
                        }
                    }
                }

                try {
                    $this->importRow($row['raw_data'], $importType, $agentId);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'row' => $index + 1,
                        'message' => $e->getMessage(),
                        'data' => $row['raw_data'],
                    ];
                }
            }

            DB::commit();

            $msg = "تم استيراد {$imported} وثيقة بنجاح";
            if ($agentsCreated > 0)
                $msg .= "، وإنشاء {$agentsCreated} وكيل جديد";
            if ($skipped > 0)
                $msg .= "، وتخطي {$skipped}";
            if (count($errors) > 0)
                $msg .= "، و" . count($errors) . " خطأ";

            return response()->json([
                'success' => true,
                'imported_count' => $imported,
                'skipped_count' => $skipped,
                'agents_created' => $agentsCreated,
                'error_count' => count($errors),
                'errors' => $errors,
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ExcelImportController@confirmImport: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء الاستيراد: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * إنشاء وكيل جديد تلقائياً من بيانات الملف
     * يُنشئ user مرتبط بالوكيل بكلمة مرور مؤقتة
     */
    private function createAgentFromRow(string $agentName, string $agencyName): BranchAgent
    {
        // توليد username فريد بناءً على timestamp (لتجنب استخدام دوال تتطلب إضافات PHP غير متوفرة)
        $baseUsername = 'agent';
        $username = $baseUsername . '_' . time() . '_' . rand(100, 999);

        // إنشاء مستخدم مرتبط بكلمة مرور مؤقتة
        $user = User::create([
            'username' => $username,
            'name' => $agentName,
            'password' => Hash::make('Temp@' . rand(10000, 99999)),
            'is_admin' => false,
            'authorized_documents' => [],
            'is_active' => true,
        ]);

        // توليد كود فريد للوكيل
        $lastAgent = BranchAgent::orderBy('id', 'desc')->first();
        $nextNumber = $lastAgent ? (int) substr($lastAgent->code, 2) + 1 : 1;
        $code = 'BK' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // إنشاء الوكيل
        $agent = BranchAgent::create([
            'type' => 'وكيل',
            'code' => $code,
            'agency_name' => $agencyName,
            'agent_name' => $agentName,
            'city' => 'غير محدد',
            'contract_date' => now()->toDateString(),
            'status' => 'غير نشط',   // غير نشط حتى يراجعه الأدمن
            'user_id' => $user->id,
            'notes' => 'تم إنشاؤه تلقائياً من استيراد Excel بتاريخ ' . now()->format('Y-m-d H:i'),
            'authorized_documents' => [],
            'document_percentages' => [],
        ]);

        return $agent;
    }

    /**
     * استيراد صف واحد بناءً على نوع التأمين
     */
    private function importRow(array $rawData, string $importType, ?int $agentId): void
    {
        // تسجيل أول صف فقط لمعرفة مفاتيح الهيدر المكتشفة
        if (!self::$firstRowLogged) {
            $stringKeys = array_filter(array_keys($rawData), fn($k) => !is_numeric($k));
            Log::info('ExcelImport[firstRow] string keys: ' . json_encode(array_values($stringKeys), JSON_UNESCAPED_UNICODE));
            Log::info('ExcelImport[firstRow] full raw: ' . json_encode($rawData, JSON_UNESCAPED_UNICODE));
            self::$firstRowLogged = true;
        }

        // نولّد رقم وثيقة فريد
        $lastDoc = InsuranceDocument::orderBy('id', 'desc')->first();
        if ($lastDoc && preg_match('/BKMCI(\d+)/', $lastDoc->insurance_number, $matches)) {
            $nextNum = (int) $matches[1] + 1;
        } else {
            $nextNum = 1;
        }
        $insuranceNumber = 'BKMCI' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        // استخراج الحقول بناءً على عناوين الإكسيل الفعلية
        // ملاحظة: من اللوج تبيّن أن رقم الوثيقة دائماً في العمود [4] واسم المؤمن في [7]
        $extractedDocNum = trim((string) ($rawData[4] ?? ''));
        if (empty($extractedDocNum)) {
            $extractedDocNum = $this->extractValue($rawData, ['رقم الوثيقة', 'وثيقة', 'insurance']);
        }
        // بحث ذكي: أي نص يحتوي على '-' مثل TR-HA1
        if (empty($extractedDocNum)) {
            foreach ($rawData as $val) {
                $v = trim((string) $val);
                if (preg_match('/^[A-Za-z]{2,}-[A-Za-z0-9]+$/', $v)) {
                    $extractedDocNum = $v;
                    break;
                }
            }
        }
        $finalInsuranceNum = !empty($extractedDocNum) ? $extractedDocNum : $insuranceNumber;

        $insuredName = trim((string) ($rawData[7] ?? ''));
        if (empty($insuredName)) {
            $insuredName = $this->extractValue($rawData, ['اسم المؤمن له', 'مؤمن', 'الاسم', 'صاحب']);
        }
        $insuredName = !empty($insuredName) ? $insuredName : '-';

        $phone = $this->extractValue($rawData, ['هاتف', 'نقال', 'موبايل']) ?? '-';

        $extractedIssueDate = $this->parseDate($this->extractValue($rawData, ['تاريخ الاصدار', 'الإصدار', 'اصدار', 'تاريخ'])) ?? now()->toDateString();
        $safeIssueDate = $extractedIssueDate . ' 12:00:00';

        $startDate = $this->parseDate($this->extractValue($rawData, ['تاريخ البداية', 'بداية', 'من تاريخ', 'start'])) ?? $extractedIssueDate;
        $endDate = $this->parseDate($this->extractValue($rawData, ['تاريخ النهاية', 'نهاية', 'الى تاريخ', 'end']));

        $premium = (float) ($this->extractValue($rawData, ['القسط الصافي', 'قسط', 'صافي', 'premium']) ?? 0);
        $tax = (float) ($this->extractValue($rawData, ['ضريبة', 'الضريبة']) ?? 1.0);
        $stamp = (float) ($this->extractValue($rawData, ['دمغة', 'الدمغة']) ?? 0.5);
        $issueFees = (float) ($this->extractValue($rawData, ['م.الاصدار', 'م اصدار', 'اصدار']) ?? 2.0);
        $supervision = (float) ($this->extractValue($rawData, ['ورقابة', 'رقابة', 'اشراف']) ?? 0.5);
        $total = (float) ($this->extractValue($rawData, ['الاجمالي', 'إجمالي', 'مبلغ', 'قيمة', 'total']) ?? ($premium + $tax + $stamp + $issueFees + $supervision));

        $chassisNum = $this->extractValue($rawData, ['هيكل', 'شاصي', 'chassis']) ?? '-';
        $plateNum = $this->extractValue($rawData, ['رقم اللوحة', 'لوحة', 'مركبة', 'plate']) ?? '-';
        $notes = $this->extractValue($rawData, ['قوة المحرك', 'محرك', 'حصان', 'ملاحظ', 'بيان', 'notes']);

        $finalStartDate = $startDate;
        $finalEndDate = $endDate ?? \Carbon\Carbon::parse($finalStartDate)->addYear()->toDateString();

        // بناء بيانات الوثيقة
        $docData = [
            'insurance_type' => 'تأمين إجباري سيارات',
            'insurance_number' => $finalInsuranceNum,
            'issue_date' => $safeIssueDate,
            'start_date' => $finalStartDate,
            'end_date' => $finalEndDate,
            'duration' => 'سنة',
            'insured_name' => $insuredName,
            'phone' => $phone,
            'chassis_number' => $chassisNum,
            'plate_number_manual' => $plateNum,
            'premium' => $premium,
            'tax' => $tax,
            'stamp' => $stamp,
            'issue_fees' => $issueFees,
            'supervision_fees' => $supervision,
            'total' => $total,
            'branch_agent_id' => $agentId,
            'notes' => $notes,
            'print_type' => 'A4',
        ];

        InsuranceDocument::updateOrCreate(
            ['insurance_number' => $docData['insurance_number']],
            $docData
        );
    }

    /**
     * خوارزمية Fuzzy Matching للعثور على أفضل تطابق للوكيل
     */
    private function findBestMatch(string $agentName, string $agencyName, $agents): array
    {
        $cacheKey = md5($agentName . '|' . $agencyName);
        if (isset($this->matchCache[$cacheKey])) {
            return $this->matchCache[$cacheKey];
        }

        $agentNameNormalized = $this->normalizeArabic($agentName);
        $agencyNameNormalized = $this->normalizeArabic($agencyName);

        $candidates = [];

        foreach ($agents as $agent) {
            $dbAgentName = $this->normalizeArabic($agent->agent_name ?? '');
            $dbAgencyName = $this->normalizeArabic($agent->agency_name ?? '');

            // حساب نسبة التشابه للاسمين
            $agentScore = !empty($agentNameNormalized) ? $this->similarityScore($agentNameNormalized, $dbAgentName) : 0;
            $agencyScore = !empty($agencyNameNormalized) ? $this->similarityScore($agencyNameNormalized, $dbAgencyName) : 0;

            // إذا كان اسم الوكيل متطابق بنسبة 95% فما فوق، نعطيه الأولوية القصوى ونتجاهل الوكالة إذا كانت غير دقيقة
            if ($agentScore >= 95) {
                $totalScore = $agentScore;
            } elseif (!empty($agentNameNormalized) && !empty($agencyNameNormalized)) {
                $totalScore = ($agentScore * 0.7) + ($agencyScore * 0.3);
            } elseif (!empty($agentNameNormalized)) {
                $totalScore = $agentScore;
            } else {
                $totalScore = $agencyScore;
            }

            if ($totalScore >= 50) { // فلتر: استبعاد التطابقات الضعيفة جداً
                $candidates[] = [
                    'id' => $agent->id,
                    'agent_name' => $agent->agent_name,
                    'agency_name' => $agent->agency_name,
                    'code' => $agent->code,
                    'status' => $agent->status,
                    'score' => round($totalScore, 1),
                ];
            }
        }

        // ترتيب حسب الدرجة تنازلياً
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

        $bestCandidate = !empty($candidates) ? $candidates[0] : null;
        $topCandidates = array_slice($candidates, 0, 5);

        if (!$bestCandidate) {
            $result = ['status' => 'not_found', 'score' => 0, 'agent' => null, 'candidates' => []];
            $this->matchCache[$cacheKey] = $result;
            return $result;
        }

        // بناءً على طلب العميل: أي نسبة فوق 50% نعتبرها تطابق تام
        if ($bestCandidate['score'] >= 50) {
            $result = ['status' => 'exact', 'score' => $bestCandidate['score'], 'agent' => $bestCandidate, 'candidates' => $topCandidates];
            $this->matchCache[$cacheKey] = $result;
            return $result;
        }

        $result = ['status' => 'not_found', 'score' => $bestCandidate['score'], 'agent' => null, 'candidates' => $topCandidates];
        $this->matchCache[$cacheKey] = $result;
        return $result;
    }

    /**
     * حساب نسبة التشابه بين نصين (0-100)
     */
    private function similarityScore(string $a, string $b): float
    {
        if ($a === $b)
            return 100.0;
        if (empty($a) || empty($b))
            return 0.0;

        // الاعتماد الكلي على مطابقة الكلمات (Tokens) لتجنب الأخطاء العشوائية
        $tokenScore = $this->tokenSimilarity($a, $b);

        return $tokenScore;
    }

    /**
     * دالة مساعدة لمقارنة كلمتين والتأكد من أنهما نفس الكلمة (حتى لو اختلف حرف الـ "ال")
     */
    private function isTokenMatch(string $t1, string $t2): bool
    {
        if ($t1 === $t2)
            return true;

        // إزالة "ال" التعريف من الكلمتين والمقارنة
        $t1_no_al = preg_replace('/^ال/u', '', $t1);
        $t2_no_al = preg_replace('/^ال/u', '', $t2);

        if ($t1_no_al === $t2_no_al && mb_strlen($t1_no_al) > 2) {
            return true;
        }

        // التحقق من الاحتواء المباشر (فقط إذا كان الفرق في الطول حرف أو حرفين كحد أقصى)
        // هذا يمنع تطابق "عبد" مع "عبدالعزيز"
        if (str_contains($t1, $t2) || str_contains($t2, $t1)) {
            $diff = abs(mb_strlen($t1) - mb_strlen($t2));
            if ($diff <= 2 && min(mb_strlen($t1), mb_strlen($t2)) >= 3) {
                return true;
            }
        }

        return false;
    }

    /**
     * مقارنة على مستوى الكلمات للتعامل مع الأسماء الناقصة (الاسم الأول والأخير فقط)
     */
    private function tokenSimilarity(string $a, string $b): float
    {
        $tokensA = array_values(array_filter(explode(' ', $a)));
        $tokensB = array_values(array_filter(explode(' ', $b)));

        if (empty($tokensA) || empty($tokensB))
            return 0.0;

        $shorter = count($tokensA) < count($tokensB) ? $tokensA : $tokensB;
        $longer = count($tokensA) < count($tokensB) ? $tokensB : $tokensA;

        $matches = 0;
        foreach ($shorter as $shortToken) {
            foreach ($longer as $longToken) {
                if ($this->isTokenMatch($shortToken, $longToken)) {
                    $matches++;
                    break;
                }
            }
        }

        // بناءً على طلب العميل: لازم يكون فيه اسمين على الأقل متطابقين (إذا كان الاسم مكون من كلمتين أو أكثر)
        if (count($shorter) >= 2 && $matches < 2) {
            return 0.0; // نرفض المطابقة تماماً لمنع الأخطاء العشوائية
        }

        $precision = $matches / count($shorter);
        $recall = $matches / count($longer);

        // إذا كانت كل كلمات الاسم القصير موجودة في الاسم الطويل
        if ($precision == 1.0) {
            return 95.0 + (5.0 * $recall);
        }

        if ($precision + $recall == 0)
            return 0.0;
        return (2 * $precision * $recall / ($precision + $recall)) * 100;
    }

    /**
     * تطبيع النص العربي (إزالة التشكيل والفروقات في الحروف والرموز)
     */
    private function normalizeArabic(string $text): string
    {
        $text = trim($text);
        // إزالة التشكيل
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}]/u', '', $text);
        // توحيد الألف
        $text = str_replace(['أ', 'إ', 'آ'], 'ا', $text);
        // توحيد الياء
        $text = str_replace('ى', 'ي', $text);
        // توحيد التاء المربوطة
        $text = str_replace('ة', 'ه', $text);
        // دمج الأسماء المركبة الشائعة (عبد، ابو، بو) لمعالجة مشكلة المسافات (مثل: "عبد المعز" و "عبدالمعز")
        $text = preg_replace('/\b(عبد|ابو|بو)\s+/u', '$1', $text);
        // استبدال الأقواس والرموز الشائعة بمسافة (لفصل الكلمات الملتصقة مثل "الجيلاني(334)")
        $text = str_replace(['(', ')', '[', ']', '-', '_', '.', ',', '/', '\\'], ' ', $text);
        // إزالة الأرقام
        $text = preg_replace('/[0-9]/u', ' ', $text);
        // إزالة المسافات الزائدة
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * قراءة ملف Excel أو CSV
     */
    private function readExcelFile(string $filePath, string $extension): array
    {
        $ext = strtolower($extension);

        if ($ext === 'csv') {
            return $this->readCsv($filePath);
        }

        if ($ext === 'xls') {
            return $this->readXlsOldFormat($filePath);
        }

        // xlsx - ZIP + XML
        return $this->readXlsxSimple($filePath);
    }

    /**
     * قراءة ملفات .xls القديمة
     * يحاول عبر COM على Windows، وإلا يطلب تحويل الملف
     */
    private function readXlsOldFormat(string $filePath): array
    {
        // ── الطريقة 1: COM object (Windows + Excel مثبت) ──────────────────
        if (class_exists('COM')) {
            try {
                $excel = new \COM('Excel.Application');
                $excel->Visible = false;
                $excel->DisplayAlerts = false;
                $workbook = $excel->Workbooks->Open(realpath($filePath));
                $sheet = $workbook->Sheets(1);

                $usedRange = $sheet->UsedRange;
                $rowCount = $usedRange->Rows->Count;
                $colCount = $usedRange->Columns->Count;

                $rows = [];
                for ($r = 1; $r <= $rowCount; $r++) {
                    $row = [];
                    for ($c = 1; $c <= $colCount; $c++) {
                        $row[] = (string) $sheet->Cells($r, $c)->Value;
                    }
                    $rows[] = $row;
                }

                $workbook->Close(false);
                $excel->Quit();
                unset($excel);

                return $rows;
            } catch (\Exception $comErr) {
                Log::warning('COM XLS read failed: ' . $comErr->getMessage());
                // نكمل للطريقة 2
            }
        }

        // ── الطريقة 2: قراءة HTML داخل XLS ────────────────────────────────
        // بعض ملفات XLS هي في الحقيقة HTML محفوظة بامتداد xls
        $content = @file_get_contents($filePath);
        if ($content && (str_contains($content, '<table') || str_contains($content, '<TABLE'))) {
            return $this->readHtmlTable($content);
        }

        // ── لا يمكن القراءة: طلب تحويل ──────────────────────────────────
        throw new \Exception(
            'ملفات .xls القديمة غير مدعومة مباشرةً. ' .
            'الرجاء فتح الملف في Excel واختيار "حفظ باسم" ← "Excel Workbook (.xlsx)" ثم رفع الملف الجديد.'
        );
    }

    /**
     * قراءة جدول HTML (XLS محفوظ كـ HTML)
     */
    private function readHtmlTable(string $html): array
    {
        $rows = [];
        // إيجاد الجداول
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $html, $trMatches);
        foreach ($trMatches[1] as $tr) {
            preg_match_all('/<t[dh][^>]*>(.*?)<\/t[dh]>/is', $tr, $tdMatches);
            $row = [];
            foreach ($tdMatches[1] as $cell) {
                $row[] = trim(strip_tags(html_entity_decode($cell, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            }
            if (!empty(array_filter($row))) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    private function readCsv(string $filePath): array
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 2000, ',')) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }

    /**
     * قراءة xlsx بدون مكتبات خارجية (ZIP + XML)
     * نسخة محسّنة تتعامل مع XML namespaces بشكل صحيح
     */
    private function readXlsxSimple(string $filePath): array
    {
        $rows = [];
        try {
            $zip = new \ZipArchive();
            if ($zip->open($filePath) !== true) {
                throw new \Exception('لا يمكن فتح الملف');
            }

            // ── قراءة shared strings ─────────────────────────────────────────
            $sharedStrings = [];
            $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
            if ($sharedStringsXml) {
                // إزالة XML namespaces لضمان عمل simplexml بشكل صحيح
                $sharedStringsXml = preg_replace('/\s+xmlns[^=]*="[^"]*"/i', '', $sharedStringsXml);
                $sharedStringsXml = preg_replace('/<(\/?)[a-zA-Z0-9]+:/', '<$1', $sharedStringsXml);

                $xml = @simplexml_load_string($sharedStringsXml);
                if ($xml) {
                    foreach ($xml->si as $si) {
                        $str = '';
                        // الطريقة 1: نص مباشر في <t>
                        if (isset($si->t)) {
                            $str = (string) $si->t;
                        }
                        // الطريقة 2: نص موزع على عدة <r><t>
                        if (empty($str) && isset($si->r)) {
                            foreach ($si->r as $r) {
                                if (isset($r->t)) {
                                    $str .= (string) $r->t;
                                }
                            }
                        }
                        // الطريقة 3 (fallback): استخراج النص من XML مباشرة
                        if (empty($str)) {
                            $str = trim(strip_tags((string) $si->asXML()));
                        }
                        $sharedStrings[] = $str;
                    }
                }
                Log::info('ExcelImport: sharedStrings count=' . count($sharedStrings) . ', first5=' . json_encode(array_slice($sharedStrings, 0, 5), JSON_UNESCAPED_UNICODE));
            }

            // ── قراءة الشيت الأول ────────────────────────────────────────────
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            if (!$sheetXml) {
                throw new \Exception('لا يمكن العثور على بيانات الشيت');
            }

            // إزالة namespaces من شيت البيانات أيضاً
            $sheetXml = preg_replace('/\s+xmlns[^=]*="[^"]*"/i', '', $sheetXml);
            $sheetXml = preg_replace('/<(\/?)[a-zA-Z0-9]+:/', '<$1', $sheetXml);

            $xml = @simplexml_load_string($sheetXml);
            if (!$xml) {
                throw new \Exception('خطأ في قراءة XML');
            }

            $maxRow = 0;
            $cells = [];

            foreach ($xml->sheetData->row as $row) {
                $rowIndex = (int) $row['r'];
                if ($rowIndex > $maxRow)
                    $maxRow = $rowIndex;
                foreach ($row->c as $cell) {
                    $cellRef = (string) $cell['r'];
                    $colLetter = preg_replace('/[0-9]/', '', $cellRef);
                    $colIndex = $this->columnLetterToIndex($colLetter);
                    $type = (string) $cell['t'];
                    $value = isset($cell->v) ? (string) $cell->v : '';

                    if ($type === 's') {
                        // نص من جدول shared strings
                        $idx = (int) $value;
                        $value = $sharedStrings[$idx] ?? '';
                    } elseif ($type === 'inlineStr') {
                        // نص مضمّن مباشرة في الخلية
                        if (isset($cell->is->t)) {
                            $value = (string) $cell->is->t;
                        } elseif (isset($cell->is->r)) {
                            $value = '';
                            foreach ($cell->is->r as $r) {
                                if (isset($r->t))
                                    $value .= (string) $r->t;
                            }
                        } else {
                            $value = trim(strip_tags($cell->asXML()));
                        }
                    } elseif ($type === 'b') {
                        $value = $value ? 'true' : 'false';
                    }
                    // للنوع 'str' و '' (أرقام): نستخدم $value كما هو

                    $cells[$rowIndex][$colIndex] = $value;
                }
            }

            // تحويل إلى مصفوفة منظمة
            for ($r = 1; $r <= $maxRow; $r++) {
                if (!isset($cells[$r])) {
                    $rows[] = [];
                    continue;
                }
                $maxCol = !empty($cells[$r]) ? max(array_keys($cells[$r])) : 0;
                $rowData = [];
                for ($c = 0; $c <= $maxCol; $c++) {
                    $rowData[] = $cells[$r][$c] ?? '';
                }
                $rows[] = $rowData;
            }
        } catch (\Exception $e) {
            Log::error('readXlsxSimple error: ' . $e->getMessage());
            throw $e;
        }

        return $rows;
    }

    private function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }

    /**
     * استخراج قيمة من الصف بناءً على كلمات مفتاحية في الهيدر
     */
    private function extractValue(array $row, array $keys)
    {
        // 1. التطابق التام أولاً
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                $val = trim((string) $row[$key]);
                if ($val !== '')
                    return $val;
            }
        }

        // 2. التطابق الجزئي (البحث عن الكلمة المفتاحية داخل اسم العمود)
        foreach ($keys as $key) {
            $searchKey = mb_strtolower(preg_replace('/\s+/u', '', $this->normalizeArabic((string) $key)));

            foreach ($row as $rowKey => $rowValue) {
                $colName = mb_strtolower(preg_replace('/\s+/u', '', $this->normalizeArabic((string) $rowKey)));

                // تخطي المفاتيح الرقمية البحتة (الفهارس) لأنها لا تحتوي على أسماء أعمدة
                if (is_numeric($rowKey) && empty($colName))
                    continue;

                // إذا كان اسم العمود يحتوي على الكلمة المفتاحية
                if ($colName !== '' && str_contains($colName, $searchKey)) {
                    $val = trim((string) $rowValue);
                    if ($val !== '')
                        return $val;
                }
            }
        }

        return null;
    }

    /**
     * تحويل التاريخ من صيغ مختلفة
     */
    private function parseDate(?string $dateStr): ?string
    {
        if (empty($dateStr))
            return null;

        // Excel serial number
        if (is_numeric($dateStr)) {
            $unixDate = ($dateStr - 25569) * 86400;
            return date('Y-m-d', $unixDate);
        }

        // صيغ تاريخ مختلفة
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $dateStr);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }

        try {
            return \Carbon\Carbon::parse($dateStr)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * جلب قائمة الوكلاء للـ dropdown في الـ frontend
     */
    public function getAgents()
    {
        $agents = BranchAgent::select('id', 'agent_name', 'agency_name', 'code', 'status')
            ->orderBy('agent_name')
            ->get();

        return response()->json($agents);
    }
}
