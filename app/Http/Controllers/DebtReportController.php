<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BranchAgent;
use App\Helpers\AgentPercentageHelper;

class DebtReportController extends Controller
{
    public function getOutstandingDebts()
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
        $agentIds = $agents->pluck('id')->toArray();
        
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
        foreach ($insuranceTables as $table) {
            if ($schema->hasTable($table)) {
                $docs = DB::table($table)->whereIn('branch_agent_id', $agentIds)->get();
                
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
        }

        // Get all payments and sum them and track last payment date
        $payments = DB::table('payment_vouchers')
            ->whereIn('branch_agent_id', $agentIds)
            ->orderBy('payment_date', 'asc') // asc order allows overriding, ending up with the latest
            ->get();

        foreach ($payments as $payment) {
            $agentId = $payment->branch_agent_id;
            if (isset($agentReport[$agentId])) {
                $agentReport[$agentId]['total_paid'] += (float)$payment->amount;
                $agentReport[$agentId]['last_payment_date'] = $payment->payment_date;
            }
        }

        $report = [];
        foreach ($agentReport as $data) {
            $outstandingDebt = $data['total_sales'] - $data['total_commissions'] - $data['total_paid'];

            // Only show agents with debt
            if ($outstandingDebt > 0) {
                $status = 'normal';
                if ($outstandingDebt > 10000) $status = 'critical';
                else if ($outstandingDebt > 5000) $status = 'warning';

                $report[] = [
                    'id' => $data['id'],
                    'agent_id' => $data['agent_id'],
                    'agency_name' => $data['agency_name'],
                    'total_debt' => (float)$outstandingDebt,
                    'last_payment_date' => $data['last_payment_date'],
                    'status' => $status,
                    'notes' => $outstandingDebt > 10000 ? 'يتطلب إجراء فوري' : 'متابعة دورية'
                ];
            }
        }

        return response()->json($report);
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
