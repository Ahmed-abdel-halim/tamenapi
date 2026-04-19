<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BranchAgent;

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
        $report = [];

        foreach ($agents as $agent) {
            $totalSales = 0;
            $totalAgentCommissions = 0;
            
            // Get percentages for this agent
            $percentages = $agent->document_percentages ?? [];
            if (is_string($percentages)) {
                $percentages = json_decode($percentages, true) ?: [];
            }

            foreach ($insuranceTables as $table) {
                if (DB::getSchemaBuilder()->hasTable($table)) {
                    $docs = DB::table($table)->where('branch_agent_id', $agent->id)->get();
                    
                    foreach ($docs as $doc) {
                        $totalSales += (float)($doc->total ?? 0);
                        
                        // Calculate commission for this specific doc
                        $premium = (float)($doc->premium ?? 0);
                        $typeName = $this->mapTableToTypeName($table, $doc);
                        $rate = (float)($percentages[$typeName] ?? 0);
                        
                        $totalAgentCommissions += ($premium * ($rate / 100));
                    }
                }
            }

            // Get total paid by agent via Payment Vouchers
            $totalPaid = DB::table('payment_vouchers')->where('branch_agent_id', $agent->id)->sum('amount');

            // Outstanding Debt = (Total Sales) - (Commissions) - (Payments)
            $outstandingDebt = $totalSales - $totalAgentCommissions - $totalPaid;

            // Only show agents with debt
            if ($outstandingDebt > 0) {
                $status = 'normal';
                if ($outstandingDebt > 10000) $status = 'critical';
                else if ($outstandingDebt > 5000) $status = 'warning';

                $lastPayment = DB::table('payment_vouchers')
                    ->where('branch_agent_id', $agent->id)
                    ->orderBy('payment_date', 'desc')
                    ->first();

                $report[] = [
                    'id' => $agent->id,
                    'agent_id' => $agent->id,
                    'agency_name' => $agent->agency_name,
                    'total_debt' => (float)$outstandingDebt,
                    'last_payment_date' => $lastPayment ? $lastPayment->payment_date : 'لا يوجد',
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
