<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportOldDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:old-documents 
                            {path? : Path to CSV file (default: storage/app/old_documents.csv)} 
                            {--force : Force overwrite/update existing records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or update old insurance policies from CSV file into insurance_documents table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $csvPath = $this->argument('path') 
            ? $this->argument('path') 
            : storage_path('app/old_documents.csv');

        if (!file_exists($csvPath)) {
            $this->error("الملف غير موجود: {$csvPath}");
            $this->line("يرجى التأكد من وجود ملف CSV في storage/app/old_documents.csv أو تمرير المسار كـ argument.");
            return 1;
        }

        $this->info("بدء استيراد الوثائق القديمة من: {$csvPath}");

        // 1. تحميل خريطة الوكلاء من الداتا بيز وقاعدة البيانات الحالية
        $this->info("1. تحميل بيانات الوكلاء والوثائق الحالية...");

        $agentMap = [];
        $mappingsFile = storage_path('app/agent_mappings.json');
        if (file_exists($mappingsFile)) {
            $agentMap = json_decode(file_get_contents($mappingsFile), true) ?: [];
        }

        // خريطة الوكلاء المباشرة من DB (code => id, agent_name => id)
        $dbAgents = DB::table('branches_agents')->get(['id', 'code', 'agent_name', 'agency_name']);
        $agentCodeToId = [];
        $agentNameToId = [];

        foreach ($dbAgents as $ag) {
            if (!empty($ag->code)) {
                $agentCodeToId[trim($ag->code)] = $ag->id;
            }
            if (!empty($ag->agent_name)) {
                $agentNameToId[$this->normalizeArabic($ag->agent_name)] = $ag->id;
            }
            if (!empty($ag->agency_name)) {
                $agentNameToId[$this->normalizeArabic($ag->agency_name)] = $ag->id;
            }
        }

        // أرقام الوثائق الموجودة حالياً
        $existingPolicies = [];
        $dbPolicies = DB::table('insurance_documents')->select('id', 'insurance_number')->get();
        foreach ($dbPolicies as $p) {
            if (!empty($p->insurance_number)) {
                $existingPolicies[strtoupper(trim($p->insurance_number))] = $p->id;
            }
        }

        // 2. قراءة ملف CSV
        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->error("تعذر فتح ملف CSV!");
            return 1;
        }

        // تخطي الـ BOM إن وجد
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            $this->error("ملف CSV فارغ!");
            fclose($handle);
            return 1;
        }

        // تنظيف أسماء الأعمدة
        $header = array_map(function ($col) {
            return trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $col));
        }, $header);

        $colMap = array_flip($header);

        $insertedCount = 0;
        $updatedCount  = 0;
        $skippedCount  = 0;
        $noAgentCount  = 0;

        // حساب عدد الصفوف الكلي للتقدم
        $totalRows = 0;
        while (!feof($handle)) {
            if (fgets($handle) !== false) $totalRows++;
        }
        rewind($handle);
        fgetcsv($handle); // تخطي الهيدر

        $bar = $this->output->createProgressBar($totalRows);
        $bar->start();

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                $bar->advance();
                if (empty($row) || count($row) < 2) continue;

                $policyNumber = isset($colMap['policy_number']) ? strtoupper(trim($row[$colMap['policy_number']])) : '';
                if (empty($policyNumber)) {
                    $skippedCount++;
                    continue;
                }

                // تحديد الوكيل
                $agentName = isset($colMap['agent']) ? trim($row[$colMap['agent']]) : '';
                if (empty($agentName) && isset($colMap['agent_name'])) {
                    $agentName = trim($row[$colMap['agent_name']]);
                }

                $branchAgentId = null;
                if (!empty($agentName)) {
                    if (isset($agentMap[$agentName])) {
                        $branchAgentId = $agentMap[$agentName];
                    } else {
                        $normName = $this->normalizeArabic($agentName);
                        if (isset($agentNameToId[$normName])) {
                            $branchAgentId = $agentNameToId[$normName];
                        }
                    }

                    // إذا كان اسم الجهة هو أدمن / إدارة / الفرع الرئيسي -> ربط بـ مكتب الاصدار (BK0063)
                    if (!$branchAgentId) {
                        $systemNames = ['admin', '.admin', 'hamza', 'المدار الليبي - المبيعات', 'الفرع الرئيسي', 'الادارة', 'مشرف النظام', 'محرر عقود'];
                        $lowAgent = strtolower($agentName);
                        foreach ($systemNames as $sysName) {
                            if (str_contains($lowAgent, strtolower($sysName))) {
                                $branchAgentId = $agentCodeToId['BK0063'] ?? 65;
                                break;
                            }
                        }
                    }
                } else {
                    // افتراضي للوثائق بدون وكيل -> مكتب الاصدار BK0063
                    $branchAgentId = $agentCodeToId['BK0063'] ?? 65;
                }

                // التواريخ
                $issueDateStr = isset($colMap['issue_date']) ? trim($row[$colMap['issue_date']]) : '';
                $issueDt = $this->parseDate($issueDateStr);
                
                $issueDateFormatted = $issueDt ? $issueDt->format('Y-m-d H:i:s') : now()->toDateTimeString();
                $startDateFormatted = $issueDt ? $issueDt->format('Y-m-d') : now()->toDateString();
                $endDateFormatted   = $issueDt ? $issueDt->copy()->addYear()->format('Y-m-d') : now()->addYear()->toDateString();

                // القيم المالية
                $premium    = $this->parseNumber($row[$colMap['net_premium']] ?? 0);
                $tax        = $this->parseNumber($row[$colMap['tax']] ?? 0);
                $stamp      = $this->parseNumber($row[$colMap['stamp_fee']] ?? 0.50);
                $issueFees  = $this->parseNumber($row[$colMap['issuance_fee']] ?? 2.00);
                $supFees    = $this->parseNumber($row[$colMap['supervision_fee']] ?? 0.50);
                $total      = $this->parseNumber($row[$colMap['total_premium']] ?? 0);

                if ($total == 0 && $premium > 0) {
                    $total = round($premium + $tax + $stamp + $issueFees + $supFees, 2);
                }

                $plateNumber = isset($colMap['plate_number']) ? trim($row[$colMap['plate_number']]) : null;
                $enginePower = isset($colMap['engine_power']) ? trim($row[$colMap['engine_power']]) : null;
                $insuredName = isset($colMap['insured_name']) ? trim($row[$colMap['insured_name']]) : null;

                $data = [
                    'is_old_document'     => 1,
                    'insurance_type'      => 'تأمين إجباري سيارات',
                    'insurance_number'    => $policyNumber,
                    'issue_date'          => $issueDateFormatted,
                    'start_date'          => $startDateFormatted,
                    'end_date'            => $endDateFormatted,
                    'duration'            => 'سنة',
                    'plate_number_manual' => $plateNumber ?: null,
                    'engine_power'        => $enginePower ?: null,
                    'insured_name'        => $insuredName ?: null,
                    'premium'             => $premium,
                    'tax'                 => $tax,
                    'stamp'               => $stamp,
                    'issue_fees'          => $issueFees,
                    'supervision_fees'    => $supFees,
                    'total'               => $total,
                    'print_type'          => 'A4',
                    'branch_agent_id'     => $branchAgentId,
                    'updated_at'          => now(),
                ];

                if (isset($existingPolicies[$policyNumber])) {
                    // تحديث الوثيقة الموجودة
                    DB::table('insurance_documents')
                        ->where('id', $existingPolicies[$policyNumber])
                        ->update($data);
                    $updatedCount++;
                } else {
                    // إضافة وثيقة جديدة
                    $data['created_at'] = $issueDateFormatted;
                    $newId = DB::table('insurance_documents')->insertGetId($data);
                    $existingPolicies[$policyNumber] = $newId;
                    $insertedCount++;
                }
            }

            DB::commit();
            fclose($handle);
            $bar->finish();
            $this->newLine();

            $this->info("=======================================");
            $this->info("✅ اكتمل استيراد وتحديث الوثائق بنجاح!");
            $this->info("=======================================");
            $this->line("➕ وثائق جديدة أضيفت:   {$insertedCount}");
            $this->line("🔄 وثائق تمت تحديثها:    {$updatedCount}");
            $this->line("⚠️  وثائق بدون وكيل:      {$noAgentCount}");
            $this->line("⏭️  صفحات متخطاة:       {$skippedCount}");
            $this->info("=======================================");

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("حدث خطأ أثناء الاستيراد: " . $e->getMessage());
            return 1;
        }
    }

    private function normalizeArabic($text)
    {
        $t = trim((string)$text);
        $t = preg_replace('/[\x{064B}-\x{065F}\x{0670}]/u', '', $t);
        $t = preg_replace('/[أإآ]/u', 'ا', $t);
        $t = str_replace(['ى', 'ة'], ['ي', 'ه'], $t);
        $t = preg_replace('/\s+/u', ' ', $t);
        return mb_strtolower(trim($t));
    }

    private function parseDate($val)
    {
        if (empty($val)) return null;
        try {
            return Carbon::parse($val);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseNumber($val)
    {
        $clean = str_replace(',', '', trim((string)$val));
        return is_numeric($clean) ? round((float)$clean, 2) : 0.0;
    }
}
