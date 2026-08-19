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
                        $hasType = $schema->hasColumn($table, 'insurance_type');
                        $hasIssueDate = $schema->hasColumn($table, 'issue_date');
                        $hasStartDate = $schema->hasColumn($table, 'start_date');

                        $selects = ['branch_agent_id'];
                        if ($hasTotal) $selects[] = 'total';
                        if ($hasPremium) $selects[] = 'premium';
                        if ($hasType) $selects[] = 'insurance_type';
                        if ($hasIssueDate) $selects[] = 'issue_date';
                        elseif ($hasStartDate) $selects[] = 'start_date';
                        else $selects[] = 'created_at';

                        $docs = DB::table($table)
                            ->select($selects)
                            ->whereIn('branch_agent_id', $agentIds)
                            ->get();

                        foreach ($docs as $doc) {
                            $agentId = $doc->branch_agent_id;
                            if (!isset($agentReport[$agentId])) continue;

                            $total = (float)($doc->total ?? 0);
                            $premium = (float)($doc->premium ?? 0);

                            $typeName = $this->mapTableToTypeName($table, $doc);
                            $docDate = $doc->issue_date ?? $doc->start_date ?? $doc->created_at ?? null;
                            $rate = AgentPercentageHelper::resolvePercentage($agentReport[$agentId]['percentages'], $typeName, $docDate);

                            $agentReport[$agentId]['total_sales'] += $total;
                            $agentReport[$agentId]['total_commissions'] += ($premium * ($rate / 100));
                        }
                    }
                } catch (\Throwable $te) {
                    Log::error("DebtReportController error on table {$table}: " . $te->getMessage());
                }
            }

            // 2. Fast Bulk Payments calculation (2 queries total for ALL agents)
            if ($schema->hasTable('payment_vouchers')) {
                try {
                    $vouchers = DB::table('payment_vouchers')
                        ->select('branch_agent_id', DB::raw('SUM(amount) as total_vouchers'), DB::raw('MAX(payment_date) as last_v_date'))
                        ->whereIn('branch_agent_id', $agentIds)
                        ->groupBy('branch_agent_id')
                        ->get();

                    foreach ($vouchers as $v) {
                        $aid = $v->branch_agent_id;
                        if (isset($agentReport[$aid])) {
                            $agentReport[$aid]['total_paid'] += (float)$v->total_vouchers;
                            if ($v->last_v_date) {
                                $agentReport[$aid]['last_payment_date'] = $v->last_v_date;
                            }
                        }
                    }
                } catch (\Throwable $ve) {
                    Log::error("DebtReportController error on payment_vouchers: " . $ve->getMessage());
                }
            }

            if ($schema->hasTable('monthly_account_closures')) {
                try {
                    $closures = DB::table('monthly_account_closures')
                        ->select('branch_agent_id', DB::raw('SUM(paid_amount) as total_closures'), DB::raw('MAX(created_at) as last_c_date'))
                        ->whereIn('branch_agent_id', $agentIds)
                        ->where('paid_amount', '>', 0)
                        ->groupBy('branch_agent_id')
                        ->get();

                    foreach ($closures as $c) {
                        $aid = $c->branch_agent_id;
                        if (isset($agentReport[$aid])) {
                            $agentReport[$aid]['total_paid'] += (float)$c->total_closures;
                            if ($c->last_c_date && $agentReport[$aid]['last_payment_date'] === 'لا يوجد') {
                                $agentReport[$aid]['last_payment_date'] = substr($c->last_c_date, 0, 10);
                            }
                        }
                    }
                } catch (\Throwable $ce) {
                    Log::error("DebtReportController error on monthly_account_closures: " . $ce->getMessage());
                }
            }

            // 3. Build response
            $report = [];
            foreach ($agentReport as $data) {
                $outstandingDebt = $data['total_sales'] - $data['total_commissions'] - $data['total_paid'];

                if ($outstandingDebt > 0) {
                    $status = 'normal';
                    if ($outstandingDebt > 10000) $status = 'critical';
                    else if ($outstandingDebt > 5000) $status = 'warning';

                    $report[] = [
                        'id' => $data['id'],
                        'agent_id' => $data['agent_id'],
                        'agency_name' => $data['agency_name'],
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
