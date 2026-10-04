<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BranchAgent;
use App\Helpers\AgentPercentageHelper;
use App\Helpers\InternationalInsuranceHelper;
use Illuminate\Support\Facades\Log;

class DebtReportController extends Controller
{
    public function getOutstandingDebts(Request $request)
    {
        try {
            @ini_set('memory_limit', '512M');
            @set_time_limit(120);

            $selectedMonth = $request->input('month'); // 'all', or 1..12
            $selectedYear = (int)($request->input('year') ?: date('Y'));
            $isSpecificMonth = (!empty($selectedMonth) && $selectedMonth !== 'all');
            $filterMonthNum = $isSpecificMonth ? (int)$selectedMonth : null;

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
                    'agent_code' => $agent->code ?? '',
                    'agent_phone' => $agent->phone ?: ($agent->office_phone ?: ''),
                    'delay_reason' => $agent->debt_delay_notes ?: ($agent->notes ?: ''),
                    'percentages' => $percentages,
                    'total_sales' => 0.0,
                    'total_commissions' => 0.0,
                    'current_month_sales' => 0.0,
                    'current_month_commissions' => 0.0,
                    'month_sales' => 0.0,
                    'month_commissions' => 0.0,
                    'month_paid' => 0.0,
                    'total_paid' => 0.0,
                    'last_payment_date' => 'لا يوجد',
                    'monthly_data' => [],
                ];
            }

            $schema = DB::getSchemaBuilder();
            $currentYear = (int)date('Y');
            $currentMonth = (int)date('n');

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

                        if ($table === 'international_insurance_documents') {
                            foreach (['document_number', 'chassis_number', 'phone', 'insured_name', 'external_policy_number'] as $extraCol) {
                                if ($schema->hasColumn($table, $extraCol) && !in_array($extraCol, $selects)) {
                                    $selects[] = $extraCol;
                                }
                            }
                        }

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
                        if ($table === 'international_insurance_documents') {
                            $docs = InternationalInsuranceHelper::deduplicateDocuments($docs);
                        }

                        foreach ($docs as $doc) {
                            $agentId = $doc->branch_agent_id;
                            if (!isset($agentReport[$agentId])) continue;

                            $premiumVal = $premiumCol ? (float)($doc->$premiumCol ?? 0) : 0;
                            $totalVal = $totalCol ? (float)($doc->$totalCol ?? 0) : $premiumVal;

                            $typeName = $this->mapTableToTypeName($table, $doc);
                            $docDate = $doc->issue_date ?? $doc->start_date ?? $doc->created_at ?? null;
                            $rate = AgentPercentageHelper::resolvePercentage($agentReport[$agentId]['percentages'], $typeName, $docDate);
                            $commVal = ($premiumVal * ($rate / 100));

                            $agentReport[$agentId]['total_sales'] += $totalVal;
                            $agentReport[$agentId]['total_commissions'] += $commVal;

                            if ($docDate) {
                                $time = strtotime($docDate);
                                if ($time !== false) {
                                    $dYear = (int)date('Y', $time);
                                    $dMonth = (int)date('n', $time);
                                    $mKey = "{$dYear}-{$dMonth}";

                                    if (!isset($agentReport[$agentId]['monthly_data'][$mKey])) {
                                        $agentReport[$agentId]['monthly_data'][$mKey] = [
                                            'sales' => 0.0,
                                            'commissions' => 0.0,
                                            'company_share' => 0.0,
                                            'paid' => 0.0
                                        ];
                                    }
                                    $agentReport[$agentId]['monthly_data'][$mKey]['sales'] += $totalVal;
                                    $agentReport[$agentId]['monthly_data'][$mKey]['commissions'] += $commVal;
                                    $agentReport[$agentId]['monthly_data'][$mKey]['company_share'] += ($totalVal - $commVal);

                                    if ($dYear === $currentYear && $dMonth === $currentMonth) {
                                        $agentReport[$agentId]['current_month_sales'] += $totalVal;
                                        $agentReport[$agentId]['current_month_commissions'] += $commVal;
                                    }

                                    if ($isSpecificMonth && $dYear === $selectedYear && $dMonth === $filterMonthNum) {
                                        $agentReport[$agentId]['month_sales'] += $totalVal;
                                        $agentReport[$agentId]['month_commissions'] += $commVal;
                                    }
                                }
                            }
                        }
                    }
                } catch (\Throwable $te) {
                    Log::error("DebtReportController error on table {$table}: " . $te->getMessage());
                }
            }

            // 2. Payments per month and overall
            if ($schema->hasTable('monthly_account_closures')) {
                try {
                    $closures = DB::table('monthly_account_closures')
                        ->whereIn('branch_agent_id', $agentIds)
                        ->select('branch_agent_id', 'year', 'month', 'paid_amount', 'notes')
                        ->get();

                    foreach ($closures as $cl) {
                        $aid = $cl->branch_agent_id;
                        if (!isset($agentReport[$aid])) continue;
                        $mKey = "{$cl->year}-{$cl->month}";
                        if (!isset($agentReport[$aid]['monthly_data'][$mKey])) {
                            $agentReport[$aid]['monthly_data'][$mKey] = [
                                'sales' => 0.0,
                                'commissions' => 0.0,
                                'company_share' => 0.0,
                                'paid' => 0.0
                            ];
                        }
                        $clPaid = (float)($cl->paid_amount ?? 0);
                        if ($clPaid > $agentReport[$aid]['monthly_data'][$mKey]['paid']) {
                            $agentReport[$aid]['monthly_data'][$mKey]['paid'] = $clPaid;
                        }

                        if ($isSpecificMonth && (int)$cl->year === $selectedYear && (int)$cl->month === $filterMonthNum) {
                            if ($clPaid > $agentReport[$aid]['month_paid']) {
                                $agentReport[$aid]['month_paid'] = $clPaid;
                            }
                            if (!empty($cl->notes) && empty($agentReport[$aid]['delay_reason'])) {
                                $agentReport[$aid]['delay_reason'] = $cl->notes;
                            }
                        }
                    }
                } catch (\Throwable $cle) {
                    Log::error("DebtReportController closures error: " . $cle->getMessage());
                }
            }

            if ($schema->hasTable('payment_vouchers')) {
                try {
                    $vouchers = DB::table('payment_vouchers')
                        ->whereIn('branch_agent_id', $agentIds)
                        ->select('branch_agent_id', 'amount', 'payment_date', 'year', 'month')
                        ->get();

                    foreach ($vouchers as $v) {
                        $aid = $v->branch_agent_id;
                        if (!isset($agentReport[$aid])) continue;
                        $vDate = $v->payment_date;
                        $vYear = $v->year;
                        $vMonth = $v->month;
                        if (!$vYear && $vDate) {
                            $t = strtotime($vDate);
                            if ($t !== false) {
                                $vYear = (int)date('Y', $t);
                                $vMonth = (int)date('n', $t);
                            }
                        }
                        if ($vYear && $vMonth) {
                            $mKey = "{$vYear}-{$vMonth}";
                            if (!isset($agentReport[$aid]['monthly_data'][$mKey])) {
                                $agentReport[$aid]['monthly_data'][$mKey] = [
                                    'sales' => 0.0,
                                    'commissions' => 0.0,
                                    'company_share' => 0.0,
                                    'paid' => 0.0
                                ];
                            }
                            if ($agentReport[$aid]['monthly_data'][$mKey]['paid'] == 0) {
                                $agentReport[$aid]['monthly_data'][$mKey]['paid'] += (float)($v->amount ?? 0);
                            }
                            if ($isSpecificMonth && (int)$vYear === $selectedYear && (int)$vMonth === $filterMonthNum) {
                                if ($agentReport[$aid]['month_paid'] == 0) {
                                    $agentReport[$aid]['month_paid'] += (float)($v->amount ?? 0);
                                }
                            }
                        }
                    }
                } catch (\Throwable $ve) {
                    Log::error("DebtReportController vouchers error: " . $ve->getMessage());
                }
            }

            // Calculate total actual paid amount from AgentPaymentHelper
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
                $totalCompanyShare = $data['total_sales'] - $data['total_commissions'];
                $totalCumulativeDebt = max(0, $totalCompanyShare - $data['total_paid']);

                if ($isSpecificMonth) {
                    $monthCompanyShare = max(0, $data['month_sales'] - $data['month_commissions']);
                    $monthDebt = max(0, $monthCompanyShare - $data['month_paid']);

                    if ($monthDebt > 0.01 || $monthCompanyShare > 0.01 || ($totalCumulativeDebt > 0.01 && $data['month_sales'] > 0)) {
                        $status = 'normal';
                        if ($monthDebt > 10000 || $totalCumulativeDebt > 10000) {
                            $status = 'critical';
                        } else if ($monthDebt > 0.01 || $totalCumulativeDebt > 0.01) {
                            $status = 'warning';
                        }

                        $report[] = [
                            'id' => $data['id'],
                            'agent_id' => $data['agent_id'],
                            'agency_name' => $data['agency_name'],
                            'agent_code' => $data['agent_code'],
                            'agent_phone' => $data['agent_phone'],
                            'delay_reason' => $data['delay_reason'],
                            'selected_month' => $filterMonthNum,
                            'selected_year' => $selectedYear,
                            // Month specific figures
                            'month_sales' => (float)round($data['month_sales'], 2),
                            'month_commissions' => (float)round($data['month_commissions'], 2),
                            'month_company_share' => (float)round($monthCompanyShare, 2),
                            'month_paid' => (float)round($data['month_paid'], 2),
                            'month_debt' => (float)round($monthDebt, 2),
                            // Legacy & cumulative figures
                            'total_sales' => (float)round($data['month_sales'], 2),
                            'total_commissions' => (float)round($data['month_commissions'], 2),
                            'company_share' => (float)round($monthCompanyShare, 2),
                            'total_paid' => (float)round($data['month_paid'], 2),
                            'total_debt' => (float)round($monthDebt > 0 ? $monthDebt : $totalCumulativeDebt, 2),
                            'cumulative_debt' => (float)round($totalCumulativeDebt, 2),
                            'past_overdue_debt' => (float)round(max(0, $totalCumulativeDebt - $monthDebt), 2),
                            'current_month_debt' => (float)round($monthDebt, 2),
                            'last_payment_date' => $data['last_payment_date'],
                            'status' => $status,
                            'notes' => !empty($data['delay_reason']) ? $data['delay_reason'] : ($monthDebt > 10000 ? 'يتطلب إجراء فوري' : ($monthDebt > 0.01 ? 'مديونية غير مسددة لهذا الشهر' : 'تم تسديد هذا الشهر'))
                        ];
                    }
                } else {
                    // Cumulative / All Months
                    if ($totalCumulativeDebt > 0.01) {
                        $currentMonthShare = max(0, $data['current_month_sales'] - $data['current_month_commissions']);
                        $pastShare = max(0, $totalCompanyShare - $currentMonthShare);

                        $pastOverdue = max(0, $pastShare - $data['total_paid']);
                        $currentMonthDebt = max(0, $totalCumulativeDebt - $pastOverdue);

                        $status = 'normal';
                        if ($pastOverdue > 10000 || $totalCumulativeDebt > 10000) {
                            $status = 'critical';
                        } else if ($pastOverdue > 0.01 || $totalCumulativeDebt > 0.01) {
                            $status = 'warning';
                        }

                        $report[] = [
                            'id' => $data['id'],
                            'agent_id' => $data['agent_id'],
                            'agency_name' => $data['agency_name'],
                            'agent_code' => $data['agent_code'],
                            'agent_phone' => $data['agent_phone'],
                            'delay_reason' => $data['delay_reason'],
                            'selected_month' => 'all',
                            'selected_year' => $selectedYear,
                            'total_sales' => (float)round($data['total_sales'], 2),
                            'total_commissions' => (float)round($data['total_commissions'], 2),
                            'company_share' => (float)round($totalCompanyShare, 2),
                            'total_paid' => (float)round($data['total_paid'], 2),
                            'total_debt' => (float)round($totalCumulativeDebt, 2),
                            'cumulative_debt' => (float)round($totalCumulativeDebt, 2),
                            'past_overdue_debt' => (float)round($pastOverdue, 2),
                            'current_month_debt' => (float)round($currentMonthDebt, 2),
                            'last_payment_date' => $data['last_payment_date'],
                            'status' => $status,
                            'notes' => !empty($data['delay_reason']) ? $data['delay_reason'] : ($pastOverdue > 10000 ? 'يتطلب إجراء فوري' : ($pastOverdue > 0.01 ? 'متأخرات سابقة قيد المتابعة' : 'إنتاج الشهر الحالي جاري'))
                        ];
                    }
                }
            }

            return response()->json($report);
        } catch (\Throwable $e) {
            Log::error("Fatal error in getOutstandingDebts: " . $e->getMessage());
            return response()->json([], 200);
        }
    }

    public function updateDebtNote(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_agent_id' => 'required|integer|exists:branches_agents,id',
                'note' => 'nullable|string|max:1000',
                'year' => 'nullable|integer',
                'month' => 'nullable',
            ]);

            $agent = BranchAgent::find($validated['branch_agent_id']);
            if (!$agent) {
                return response()->json(['success' => false, 'message' => 'الوكيل غير موجود'], 404);
            }

            $note = trim($validated['note'] ?? '');
            $agent->debt_delay_notes = $note;
            $agent->save();

            // If a specific month & year are provided, update monthly_account_closures notes too
            if (!empty($validated['year']) && !empty($validated['month']) && $validated['month'] !== 'all') {
                $closure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $agent->id)
                    ->where('year', (int)$validated['year'])
                    ->where('month', (int)$validated['month'])
                    ->first();

                if ($closure) {
                    $closure->notes = $note;
                    $closure->save();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ وتحديث سبب تأخير السداد والملاحظة بنجاح',
                'delay_reason' => $note,
                'agent_id' => $agent->id,
            ]);
        } catch (\Throwable $e) {
            Log::error("Error in updateDebtNote: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء حفظ الملاحظة: ' . $e->getMessage()], 500);
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
