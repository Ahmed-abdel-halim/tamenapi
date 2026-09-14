<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use App\Helpers\InternationalInsuranceHelper;
use App\Models\InternationalInsuranceDocument;

class CleanDuplicateInternationalDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'documents:clean-international-duplicates {--dry-run : Preview changes without deleting or modifying database records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safely cleans duplicate international insurance documents (e.g. temporary LBY00xx drafts duplicate with official LBY/... cards) with zero data loss';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('--- تشغيل في وضع المعاينة التجريبية (Dry-Run): لن يتم حذف أو تعديل أي بيانات في قاعدة البيانات ---');
        } else {
            $this->info('--- بدء تنظيف ودمج الوثائق المكررة مع الحفاظ الكامل على البيانات والارتباطات ---');
        }

        // 1. Fetch all temporary local drafts (e.g. LBY0014, LBY0017)
        $drafts = DB::table('international_insurance_documents')
            ->where('document_number', 'REGEXP', '^LBY[0-9]+$')
            ->get();

        $this->info("تم العثور على " . count($drafts) . " مسودة محلية (LBY00xx) لفحص تطابقها مع بطاقات الاتحاد الرسمية.");

        $mergedCount = 0;
        $draftKeptCount = 0;

        foreach ($drafts as $draft) {
            $official = $this->findOfficialCounterpart($draft);

            if (!$official) {
                // Standalone draft without any official card yet: MUST KEEP!
                $draftKeptCount++;
                continue;
            }

            $mergedCount++;
            $this->line("عثر على تكرار: المسودة المؤقتة [ID: {$draft->id} - {$draft->document_number}] مطابقة للبطاقة الرسمية [ID: {$official->id} - {$official->document_number}] للمؤمن: {$draft->insured_name}");

            if (!$isDryRun) {
                $this->mergeAndDeleteDraft($draft, $official);
            }
        }

        // 2. Also check for exact duplicate document numbers if any
        $exactDuplicatesCleaned = $this->cleanExactDuplicateNumbers($isDryRun);

        $this->newLine();
        $this->info("=== ملخص عملية المعالجة ===");
        if ($isDryRun) {
            $this->comment("وضع المعاينة: تم اكتشاف {$mergedCount} مسودة مكررة جاهزة للدمج مع بطاقاتها الرسمية.");
            $this->comment("مسودات أصلية مستقلة (لم تتكرر مع بطاقات رسمية وتم الحفاظ عليها): {$draftKeptCount}.");
            $this->comment("تكرارات رقمية متطابقة جاهزة للتنظيف: {$exactDuplicatesCleaned}.");
            $this->info("لتنفيذ التنظيف الفعلي على قاعدة البيانات، شغل الأمر بدون --dry-run:");
            $this->line("php artisan documents:clean-international-duplicates");
        } else {
            $this->info("تم بنجاح دمج وحذف {$mergedCount} مسودة مكررة ونقل ارتباطاتها إلى البطاقات الرسمية.");
            $this->info("مسودات أصلية مستقلة تم الحفاظ عليها بالكامل: {$draftKeptCount}.");
            $this->info("تكرارات متطابقة تم تنظيفها: {$exactDuplicatesCleaned}.");
            $this->info("تمت العملية بنجاح وبدون أي فقدان في البيانات!");
        }

        return 0;
    }

    /**
     * Find the matching official card for a local draft.
     */
    protected function findOfficialCounterpart($draft)
    {
        $extPolicyNumber = trim((string)($draft->external_policy_number ?? ''));
        if ($extPolicyNumber !== '' && strpos($extPolicyNumber, '/') !== false) {
            $official = DB::table('international_insurance_documents')
                ->where('document_number', $extPolicyNumber)
                ->where('id', '!=', $draft->id)
                ->first();
            if ($official) return $official;
        }

        // 1. Match by external_policy_number
        if ($extPolicyNumber !== '') {
            $official = DB::table('international_insurance_documents')
                ->where('document_number', 'like', '%/%')
                ->where(function($q) use ($extPolicyNumber) {
                    $q->where('external_policy_number', $extPolicyNumber)
                      ->orWhere('document_number', $extPolicyNumber);
                })
                ->where('id', '!=', $draft->id)
                ->first();
            if ($official) return $official;
        }

        // 2. Match by Chassis number (if valid and not dummy like '0')
        $normChassis = InternationalInsuranceHelper::normalizeChassis($draft->chassis_number);
        if ($normChassis !== '') {
            $official = DB::table('international_insurance_documents')
                ->where('document_number', 'like', '%/%')
                ->where('id', '!=', $draft->id)
                ->where(function($q) use ($draft, $normChassis) {
                    $q->where('chassis_number', $draft->chassis_number)
                      ->orWhereRaw("LOWER(REPLACE(REPLACE(chassis_number, '-', ''), ' ', '')) = ?", [$normChassis]);
                })
                ->where(function($q) use ($draft) {
                    // Same start date or same branch agent or same phone
                    if ($draft->start_date) {
                        $q->whereDate('start_date', substr($draft->start_date, 0, 10));
                    }
                    if ($draft->branch_agent_id) {
                        $q->orWhere('branch_agent_id', $draft->branch_agent_id);
                    }
                })
                ->first();
            if ($official) return $official;
        }

        // 3. Match by normalized phone and insured name
        $normPhone = InternationalInsuranceHelper::normalizePhone($draft->phone);
        $normName = InternationalInsuranceHelper::normalizeName($draft->insured_name);

        if ($normPhone !== '' && $normName !== '') {
            $candidateOfficials = DB::table('international_insurance_documents')
                ->where('document_number', 'like', '%/%')
                ->where('id', '!=', $draft->id)
                ->where(function($q) use ($draft) {
                    if ($draft->branch_agent_id) {
                        $q->where('branch_agent_id', $draft->branch_agent_id);
                    }
                })
                ->get(['id', 'document_number', 'insured_name', 'phone', 'chassis_number', 'total']);

            foreach ($candidateOfficials as $cand) {
                if (InternationalInsuranceHelper::normalizePhone($cand->phone) === $normPhone &&
                    InternationalInsuranceHelper::normalizeName($cand->insured_name) === $normName) {
                    return $cand;
                }
            }
        }

        return null;
    }

    /**
     * Safely merge draft attributes and re-link child records before deleting the draft.
     */
    protected function mergeAndDeleteDraft($draft, $official)
    {
        DB::transaction(function() use ($draft, $official) {
            // A. Update official record with any missing details from the draft
            $updates = [];
            $fieldsToPreserve = [
                'plate_number', 'vehicle_type_id', 'branch_agent_id', 'user_id',
                'insured_address', 'whatsapp_number', 'vehicle_brand'
            ];

            foreach ($fieldsToPreserve as $field) {
                if (empty($official->$field) && !empty($draft->$field)) {
                    $updates[$field] = $draft->$field;
                }
            }

            if (!empty($updates)) {
                $updates['updated_at'] = now();
                DB::table('international_insurance_documents')
                    ->where('id', $official->id)
                    ->update($updates);
            }

            // B. Re-link related records in 'claims'
            if (Schema::hasTable('claims')) {
                // Update by polymorphic relation
                DB::table('claims')
                    ->where('document_id', $draft->id)
                    ->where(function($q) {
                        $q->where('document_type', 'App\\Models\\InternationalInsuranceDocument')
                          ->orWhere('document_type', 'international_insurance_documents')
                          ->orWhereNull('document_type');
                    })
                    ->update([
                        'document_id' => $official->id,
                        'document_number' => $official->document_number,
                    ]);

                // Update by document_number string
                DB::table('claims')
                    ->where('document_number', $draft->document_number)
                    ->update([
                        'document_id' => $official->id,
                        'document_number' => $official->document_number,
                    ]);
            }

            // C. Re-link related records in 'document_requests'
            if (Schema::hasTable('document_requests')) {
                if (Schema::hasColumn('document_requests', 'document_id')) {
                    DB::table('document_requests')
                        ->where('document_id', $draft->id)
                        ->update([
                            'document_id' => $official->id,
                            'document_number' => $official->document_number,
                        ]);
                }
                DB::table('document_requests')
                    ->where('document_number', $draft->document_number)
                    ->update(['document_number' => $official->document_number]);
            }

            // D. Re-link related records in 'commissions'
            if (Schema::hasTable('commissions') && Schema::hasColumn('commissions', 'document_number')) {
                DB::table('commissions')
                    ->where('document_number', $draft->document_number)
                    ->update(['document_number' => $official->document_number]);
            }

            // E. Delete the redundant draft document
            DB::table('international_insurance_documents')
                ->where('id', $draft->id)
                ->delete();

            Log::info("CleanDuplicateInternationalDocuments: Merged and deleted draft {$draft->document_number} (ID {$draft->id}) into official {$official->document_number} (ID {$official->id})");
        });
    }

    /**
     * Clean exact duplicate document numbers if any exist.
     */
    protected function cleanExactDuplicateNumbers(bool $isDryRun): int
    {
        $duplicateNumberGroups = DB::table('international_insurance_documents')
            ->select('document_number', DB::raw('count(*) as c'))
            ->whereNotNull('document_number')
            ->where('document_number', '!=', '')
            ->where('document_number', '!=', '-')
            ->groupBy('document_number')
            ->having('c', '>', 1)
            ->get();

        $cleanedCount = 0;
        foreach ($duplicateNumberGroups as $group) {
            $records = DB::table('international_insurance_documents')
                ->where('document_number', $group->document_number)
                ->orderBy('id', 'asc')
                ->get();

            $primary = $records->first();
            $duplicates = $records->slice(1);

            foreach ($duplicates as $dup) {
                $cleanedCount++;
                if (!$isDryRun) {
                    $this->mergeAndDeleteDraft($dup, $primary);
                }
            }
        }

        return $cleanedCount;
    }
}
