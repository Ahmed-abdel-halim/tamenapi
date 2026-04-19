<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\BranchAgent;
use App\Models\Commission;

class MigrateExistingCommissions extends Command
{
    protected $signature = 'commissions:migrate';
    protected $description = 'Migrate existing insurance documents to the commissions table';

    public function handle()
    {
        $insuranceTables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
            'school_student_insurance_documents',
            'cargo_insurance_documents',
            'cash_in_transit_insurance_documents'
        ];

        $agents = BranchAgent::all();
        $this->info('Starting commission migration for ' . $agents->count() . ' agents...');

        foreach ($agents as $agent) {
            $percentages = $agent->document_percentages ?? [];
            if (is_string($percentages)) {
                $percentages = json_decode($percentages, true) ?: [];
            }

            foreach ($insuranceTables as $table) {
                if (!DB::getSchemaBuilder()->hasTable($table)) continue;

                $docs = DB::table($table)->where('branch_agent_id', $agent->id)->get();
                
                foreach ($docs as $doc) {
                    // Check if already migrated
                    $exists = Commission::where('document_number', $doc->document_number ?? $doc->id)->exists();
                    if ($exists) continue;

                    $typeName = $this->mapTableToTypeName($table, $doc);
                    $rate = (float)($percentages[$typeName] ?? 0);
                    $premium = (float)($doc->premium ?? 0);
                    $commissionAmount = ($premium * ($rate / 100));

                    if ($commissionAmount > 0) {
                        Commission::create([
                            'branch_agent_id' => $agent->id,
                            'document_type' => $typeName,
                            'document_number' => $doc->document_number ?? (string)$doc->id,
                            'total_amount' => $doc->total ?? 0,
                            'commission_rate' => $rate,
                            'commission_amount' => $commissionAmount,
                            'status' => 'pending',
                            'notes' => 'Migrated from legacy records'
                        ]);
                    }
                }
            }
        }

        $this->info('Migration completed successfully!');
    }

    private function mapTableToTypeName($table, $doc)
    {
        $map = [
            'insurance_documents' => $doc->insurance_type ?? 'تأمين سيارات',
            'international_insurance_documents' => 'تأمين سيارات دولي',
            'travel_insurance_documents' => 'تأمين المسافرين',
            'resident_insurance_documents' => 'تأمين الوافدين',
            'marine_structure_insurance_documents' => 'تأمين الهياكل البحرية',
            'professional_liability_insurance_documents' => 'تأمين المسؤولية المهنية (الطبية)',
            'personal_accident_insurance_documents' => 'تأمين الحوادث الشخصية',
            'school_student_insurance_documents' => 'تأمين حماية طلاب المدارس',
            'cargo_insurance_documents' => 'تأمين شحن البضائع',
            'cash_in_transit_insurance_documents' => 'تأمين نقل النقدية'
        ];
        return $map[$table] ?? 'أخرى';
    }
}
