<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BranchAgent;
use App\Helpers\AgentPercentageHelper;
use Illuminate\Support\Facades\Log;

class DebtReportController extends Controller
{
    public function getOutstandingDebts()
    {
        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(120);

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
            $agentIds = $agents->pluck('id')->toArray();
            if (empty($agentIds)) {
                return response()->json([]);
            }
            
            $agentReport = [];
            foreach ($agents as $agent) {
                $percentages = $agent->document_percentages ?? [];
                if (is_string($percentages)) {
                    $percentages = json_decode($percentages, true) ?: [];
                }
                $agentReport[$agent->id] = [
                    'id' => $agent->id,
                    'agent_id' => $agent->id,
                    'agency_name' => $agent->agency_name,
                    'percentages' => $percentages,
                    'total_sales' => 0.0,
                    'total_commissions' => 0.0,
                    'total_paid' => 0.0,
                    'last_payment_date' => 'لا يوجد',
                ];
            }

            $schema = DB::getSchemaBuilder();

            // 1. Calculate sales and commissions across insurance tables
            foreach ($insuranceTables as $table) {
                try {
                    if ($schema->hasTable($table) && $schema->hasColumn($table, 'branch_agent_id')) {
                        $hasTotal = $schema->hasColumn($table, 'total');
                        $hasPremium = $schema->hasColumn($table, 'premium');
                        $hasPremiumAmount = $schema->hasColumn($table, 'premium_amount');
                        $hasSumInsured = $schema->hasColumn($table, 'sum_insured');
                        $hasType = $schema->hasColumn($table, 'insurance_type');
                        $hasIssueDate = $schema->hasColumn($table, 'issue_date');
                        $hasStartDate = $schema->hasColumn($table, 'start_date');

                        $totalCol = $hasTotal ? 'total' : ($hasSumInsured ? 'sum_insured' : null);
                        $premiumCol = $hasPremium ? 'premium' : ($hasPremiumAmount ? 'premium_amount' : null);

                        $selects = ['branch_agent_id'];
                        if ($totalCol) $selects[] = $totalCol;
                        if ($premiumCol) $selects[] = $premiumCol;
                        if ($hasType) $selects[] = 'insurance_type';
                        if ($hasIssueDate) $selects[] = 'issue_date';
                        elseif ($hasStartDate) $selects[] = 'start_date';
                        else $selects[] = 'created_at';

                        $query = DB::table($table)
                            ->select($selects)
                            ->whereIn('branch_agent_id', $agentIds);

                        if ($schema->hasColumn($table, 'is_canceled')) {
                            $query->where(function ($q) {
                                $q->whereNull('is_canceled')
                                  ->orWhere('is_canceled', 0)
                                  ->orWhere('is_canceled', false);
                            });
                        }

                        $docs = $query->get();

                        foreach ($docs as $doc) {
                            $agentId = $doc->branch_agent_id;
                            if (!isset($agentReport[$agentId])) continue;

                            $premiumVal = $premiumCol ? (float)($doc->$premiumCol ?? 0) : 0;
                            $totalVal = $totalCol ? (float)($doc->$totalCol ?? 0) : $premiumVal;

                            $typeName = $this->mapTableToTypeName($table, $doc);
                            $docDate = $doc->issue_date ?? $doc->start_date ?? $doc->created_at ?? null;
                            $rate = AgentPercentageHelper::resolvePercentage($agentReport[$agentId]['percentages'], $typeName, $docDate);

                            $agentReport[$agentId]['total_sales'] += $totalVal;
                            $agentReport[$agentId]['total_commissions'] += ($premiumVal * ($rate / 100));
                        }
                    }
                } catch (\Throwable $te) {
                    Log::error("DebtReportController error on table {$table}: " . $te->getMessage());
                }
            }

            // 2. Bulk Payments calculation using AgentPaymentHelper
            foreach ($agentReport as $aid => $data) {
                try {
                    $totalPaid = \App\Helpers\AgentPaymentHelper::getTotalPaid((int)$aid);
                    $agentReport[$aid]['total_paid'] = $totalPaid;

                    if ($schema->hasTable('payment_vouchers')) {
                        $lastVDate = DB::table('payment_vouchers')
                            ->where('branch_agent_id', $aid)
                            ->max('payment_date');
                        if ($lastVDate) {
                            $agentReport[$aid]['last_payment_date'] = $lastVDate;
                        }
                    }
                } catch (\Throwable $ve) {
                    Log::error("DebtReportController error calculating payments for agent {$aid}: " . $ve->getMessage());
                }
            }

            // 3. Build response
            $report = [];
            foreach ($agentReport as $data) {
                $companyShare = $data['total_sales'] - $data['total_commissions'];
                $outstandingDebt = $companyShare - $data['total_paid'];

                if ($outstandingDebt > 0) {
                    $status = 'normal';
                    if ($outstandingDebt > 10000) $status = 'critical';
                    else if ($outstandingDebt > 5000) $status = 'warning';

                    $report[] = [
                        'id' => $data['id'],
                        'agent_id' => $data['agent_id'],
                        'agency_name' => $data['agency_name'],
                        'total_sales' => (float)round($data['total_sales'], 2),
                        'total_commissions' => (float)round($data['total_commissions'], 2),
                        'company_share' => (float)round($companyShare, 2),
                        'total_paid' => (float)round($data['total_paid'], 2),
                        'total_debt' => (float)round($outstandingDebt, 2),
                        'last_payment_date' => $data['last_payment_date'],
                        'status' => $status,
                        'notes' => $outstandingDebt > 10000 ? 'يتطلب إجراء فوري' : 'متابعة دورية'
                    ];
                }
            }

            return response()->json($report);
        } catch (\Throwable $e) {
            Log::error("Fatal error in getOutstandingDebts: " . $e->getMessage());
            return response()->json([], 200);
        }
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
