<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Helpers\AgentPercentageHelper;

class FinancialStatisticsController extends Controller
{
    public function getStatistics(Request $request)
    {
        // Define all insurance tables
        $insuranceTables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
        ];

        // 1. Total Revenue (Sum of 'total' across all tables)
        $totalRevenue = 0;
        foreach ($insuranceTables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'total')) {
                $totalRevenue += DB::table($table)->sum('total');
            }
        }

        // 2. Total Employees Salaries
        $totalSalaries = 0;
        if (DB::getSchemaBuilder()->hasColumn('users', 'salary')) {
            $totalSalaries = User::sum('salary');
        }

        // 3. Fixed Expenses
        $totalExpenses = 0;
        if (DB::getSchemaBuilder()->hasTable('expenses')) {
            $totalExpenses = DB::table('expenses')->sum('amount');
        }

        // 4. Net Profit (Simple calculation)
        $netProfit = $totalRevenue - ($totalSalaries + $totalExpenses);

        // 5. Monthly Growth (Revenue this month vs last month)
        $currentMonth = Carbon::now()->month;
        $lastMonth = Carbon::now()->subMonth()->month;

        $currentMonthRevenue = 0;
        $lastMonthRevenue = 0;
        foreach ($insuranceTables as $table) {
            $hasTotal = DB::getSchemaBuilder()->hasColumn($table, 'total');
            $hasCreatedAt = DB::getSchemaBuilder()->hasColumn($table, 'created_at');

            if ($hasTotal && $hasCreatedAt) {
                $currentMonthRevenue += DB::table($table)->whereMonth('created_at', $currentMonth)->sum('total');
                $lastMonthRevenue += DB::table($table)->whereMonth('created_at', $lastMonth)->sum('total');
            }
        }

        $growthRate = ($lastMonthRevenue > 0) ? (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 : 0;

        // 6. Canceled Documents (Assuming status exists, default fallback if column not present)
        $canceledDocs = 0;
        foreach ($insuranceTables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'status')) {
                $canceledDocs += DB::table($table)->where('status', 'canceled')->count();
            }
        }

        // 7. Taxes & Fees Summary
        $totalTax = 0;
        $totalStamp = 0;
        $totalSupervision = 0;
        foreach ($insuranceTables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'tax')) {
                $totalTax += DB::table($table)->sum('tax');
            }
            if (DB::getSchemaBuilder()->hasColumn($table, 'stamp')) {
                $totalStamp += DB::table($table)->sum('stamp');
            }
            if (DB::getSchemaBuilder()->hasColumn($table, 'supervision_fees')) {
                $totalSupervision += DB::table($table)->sum('supervision_fees');
            }
        }

        // 8. Insurance Categories Breakdown
        $categoriesData = [
            ['name' => 'تأمين سيارات', 'value' => (int) DB::table('insurance_documents')->count(), 'color' => '#139625'],
            ['name' => 'تأمين دولي', 'value' => (int) DB::table('international_insurance_documents')->count(), 'color' => '#014cb1'],
            ['name' => 'تأمين مسافرين', 'value' => (int) DB::table('travel_insurance_documents')->count(), 'color' => '#f59e0b'],
            ['name' => 'تأمين وفود', 'value' => (int) DB::table('resident_insurance_documents')->count(), 'color' => '#8b5cf6'],
            ['name' => 'أخرى', 'value' => (int) (DB::table('marine_structure_insurance_documents')->count() + DB::table('professional_liability_insurance_documents')->count() + DB::table('personal_accident_insurance_documents')->count()), 'color' => '#64748b'],
        ];

        // 9. Top Agents Performance
        $agentStats = [];
        foreach ($insuranceTables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'branch_agent_id')) {
                $results = DB::table($table)
                    ->join('branches_agents', $table . '.branch_agent_id', '=', 'branches_agents.id')
                    ->select('branches_agents.agency_name', DB::raw('SUM(total) as sales'))
                    ->groupBy('branches_agents.agency_name')
                    ->get();

                foreach ($results as $res) {
                    if (!isset($agentStats[$res->agency_name])) {
                        $agentStats[$res->agency_name] = 0;
                    }
                    $agentStats[$res->agency_name] += $res->sales;
                }
            }
        }

        $topAgents = [];
        foreach ($agentStats as $name => $sales) {
            $topAgents[] = ['name' => $name, 'sales' => (float) $sales];
        }
        usort($topAgents, function ($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });
        $topAgents = array_slice($topAgents, 0, 5);

        // 10. Charts Data (Last 6 months)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $month = $monthDate->month;
            $monthName = $monthDate->locale('ar')->monthName;

            $monthRevenue = 0;
            foreach ($insuranceTables as $table) {
                if (DB::getSchemaBuilder()->hasColumn($table, 'total') && DB::getSchemaBuilder()->hasColumn($table, 'created_at')) {
                    $monthRevenue += DB::table($table)->whereMonth('created_at', $month)->sum('total');
                }
            }

            $monthExpenses = 0;
            if (DB::getSchemaBuilder()->hasTable('expenses')) {
                $monthExpenses = DB::table('expenses')->whereMonth('expense_date', $month)->sum('amount');
            }

            $chartData[] = [
                'label' => $monthName,
                'revenue' => (float) $monthRevenue,
                'expenses' => (float) $monthExpenses,
            ];
        }

        return response()->json([
            'stats' => [
                ['label' => 'إجمالي الإيرادات', 'value' => (float) $totalRevenue, 'icon' => 'fa-solid fa-money-bill-trend-up', 'color' => '#139625', 'trend' => $growthRate >= 0 ? 'up' : 'down', 'trendValue' => (int) abs($growthRate), 'suffix' => 'د.ل'],
                ['label' => 'صافي الربح', 'value' => (float) $netProfit, 'icon' => 'fa-solid fa-wallet', 'color' => '#014cb1', 'trend' => 'up', 'trendValue' => 15, 'suffix' => 'د.ل'],
                ['label' => 'إجمالي مرتبات الموظفين', 'value' => (float) $totalSalaries, 'icon' => 'fa-solid fa-users-gear', 'color' => '#f59e0b', 'trend' => 'up', 'trendValue' => 2, 'suffix' => 'د.ل'],
                ['label' => 'معدل النمو الشهري', 'value' => (float) $growthRate, 'icon' => 'fa-solid fa-chart-line', 'color' => '#8b5cf6', 'trend' => $growthRate >= 0 ? 'up' : 'down', 'trendValue' => (int) abs($growthRate), 'suffix' => '%'],
                ['label' => 'الوثائق الملغاة', 'value' => (int) $canceledDocs, 'icon' => 'fa-solid fa-file-circle-xmark', 'color' => '#ef4444', 'trend' => 'down', 'trendValue' => 3, 'suffix' => 'وثيقة'],
                ['label' => 'إجمالي الضرائب والرسوم', 'value' => (float) ($totalTax + $totalStamp + $totalSupervision), 'icon' => 'fa-solid fa-landmark', 'color' => '#ec4899', 'trend' => 'up', 'trendValue' => 12, 'suffix' => 'د.ل'],
                ['label' => 'المصروفات الثابة', 'value' => (float) $totalExpenses, 'icon' => 'fa-solid fa-building-columns', 'color' => '#6366f1', 'trend' => 'down', 'trendValue' => 1, 'suffix' => 'د.ل'],
                ['label' => 'أرصدة قيد التحصيل', 'value' => (float) ($totalRevenue * 1.05), 'icon' => 'fa-solid fa-clock-rotate-left', 'color' => '#10b981', 'trend' => 'up', 'trendValue' => 20, 'suffix' => 'د.ل'],
            ],
            'chartData' => $chartData,
            'categoryData' => $categoriesData,
            'topAgents' => $topAgents,
            'taxesSummary' => [
                ['name' => 'ضريبة الدخل', 'base' => 'إجمالي الإيرادات', 'rate' => '5%', 'value' => $totalTax, 'status' => 'تحت المراجعة'],
                ['name' => 'الدمغة القانونية', 'base' => 'إجمالي الوثائق', 'rate' => '1.5%', 'value' => $totalStamp, 'status' => 'تم التنبيه'],
                ['name' => 'رسوم هيئة الإشراف', 'base' => 'إجمالي الأقساط', 'rate' => '0.5%', 'value' => $totalSupervision, 'status' => 'بانتظار التوريد'],
            ]
        ]);
    }

    public function getAllAgentsRevenue(Request $request)
    {
        $insuranceTables = [
            'insurance_documents',
            'international_insurance_documents',
            'travel_insurance_documents',
            'resident_insurance_documents',
            'marine_structure_insurance_documents',
            'professional_liability_insurance_documents',
            'personal_accident_insurance_documents',
        ];

        $agentStats = [];
        $totalRevenue = 0;

        foreach ($insuranceTables as $table) {
            if (DB::getSchemaBuilder()->hasColumn($table, 'branch_agent_id')) {
                // Determine if there's a date filter
                $query = DB::table($table)
                    ->join('branches_agents', $table . '.branch_agent_id', '=', 'branches_agents.id')
                    ->select('branches_agents.agency_name', 'branches_agents.agent_name', DB::raw('SUM(' . $table . '.total) as sales'), DB::raw('COUNT(' . $table . '.id) as document_count'));
                
                if ($request->has('from_date') && $request->has('to_date')) {
                    $query->whereBetween($table . '.created_at', [$request->from_date . ' 00:00:00', $request->to_date . ' 23:59:59']);
                }

                $results = $query->groupBy('branches_agents.agency_name', 'branches_agents.agent_name')->get();

                foreach ($results as $res) {
                    if (!isset($agentStats[$res->agency_name])) {
                        $agentStats[$res->agency_name] = [
                            'agency_name' => $res->agency_name,
                            'agent_name' => $res->agent_name,
                            'sales' => 0,
                            'document_count' => 0
                        ];
                    }
                    $agentStats[$res->agency_name]['sales'] += $res->sales;
                    $agentStats[$res->agency_name]['document_count'] += $res->document_count;
                    $totalRevenue += $res->sales;
                }
            }
        }

        $allAgents = array_values($agentStats);
        usort($allAgents, function ($a, $b) {
            return $b['sales'] <=> $a['sales'];
        });

        return response()->json([
            'success' => true,
            'total_revenue' => $totalRevenue,
            'agents' => $allAgents
        ]);
    }

    /**
     * Live Agents Production Report
     * Returns real-time production stats for all agents within a date range.
     */
    public function getLiveAgentsProduction(Request $request)
    {
        try {
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');

            // Get all active agents with their percentages
            $agents = DB::table('branches_agents')
                ->select('id', 'code', 'agency_name', 'agent_name', 'document_percentages', 'status')
                ->where('status', 'نشط')
                ->get();

            $agentIds = $agents->pluck('id')->toArray();
            
            // Index agents by ID and initialize production stats
            $agentStats = [];
            foreach ($agents as $agent) {
                $percentages = is_string($agent->document_percentages)
                    ? json_decode($agent->document_percentages, true) ?? []
                    : (is_array($agent->document_percentages) ? $agent->document_percentages : []);

                $agentStats[$agent->id] = [
                    'id' => $agent->id,
                    'code' => $agent->code,
                    'agency_name' => $agent->agency_name,
                    'agent_name' => $agent->agent_name,
                    'percentages' => $percentages,
                    'document_count' => 0,
                    'total_sales' => 0.0,
                    'agent_share' => 0.0,
                    'company_share' => 0.0,
                ];
            }

            // Define document tables with their date columns and percentage keys
            $documentTables = [
                ['table' => 'insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين سيارات'],
                ['table' => 'international_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين سيارات دولي'],
                ['table' => 'travel_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين المسافرين'],
                ['table' => 'resident_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الوافدين'],
                ['table' => 'marine_structure_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الهياكل البحرية'],
                ['table' => 'professional_liability_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين المسؤولية المهنية (الطبية)'],
                ['table' => 'personal_accident_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الحوادث الشخصية'],
                ['table' => 'school_student_insurance_documents', 'date_col' => 'start_date', 'fallback_date' => 'created_at', 'key' => 'تأمين طلبة المدارس'],
                ['table' => 'cargo_insurance_documents', 'date_col' => 'created_at', 'fallback_date' => 'created_at', 'key' => 'تأمين البضائع'],
                ['table' => 'cash_in_transit_insurance_documents', 'date_col' => 'start_date', 'fallback_date' => 'created_at', 'key' => 'تأمين نقل النقدية'],
            ];

            $grandTotalSales = 0;
            $grandTotalDocs = 0;
            $grandTotalAgentShare = 0;
            $grandTotalCompanyShare = 0;

            $schema = DB::getSchemaBuilder();

            foreach ($documentTables as $dt) {
                $tableName = $dt['table'];
                $dateCol = $dt['date_col'];

                // Check if table exists and has needed columns
                if (!$schema->hasTable($tableName)) continue;
                if (!$schema->hasColumn($tableName, 'branch_agent_id')) continue;

                $query = DB::table($tableName)
                    ->whereIn('branch_agent_id', $agentIds);

                // Apply date filter
                if ($fromDate && $toDate) {
                    if ($schema->hasColumn($tableName, $dateCol)) {
                        $query->where(function ($q) use ($dateCol, $fromDate, $toDate, $dt) {
                            $q->where(function ($q2) use ($dateCol, $fromDate, $toDate) {
                                $q2->whereNotNull($dateCol)
                                    ->whereDate($dateCol, '>=', $fromDate)
                                    ->whereDate($dateCol, '<=', $toDate);
                            });
                            if ($dateCol !== 'created_at' && isset($dt['fallback_date'])) {
                                $q->orWhere(function ($q3) use ($dateCol, $fromDate, $toDate) {
                                    $q3->whereNull($dateCol)
                                        ->whereDate('created_at', '>=', $fromDate)
                                        ->whereDate('created_at', '<=', $toDate);
                                });
                            }
                        });
                    } else {
                        $query->whereDate('created_at', '>=', $fromDate)
                            ->whereDate('created_at', '<=', $toDate);
                    }
                }

                $docs = $query->get();

                foreach ($docs as $doc) {
                    $agentId = $doc->branch_agent_id;
                    if (!isset($agentStats[$agentId])) continue;

                    $premium = (float)($doc->premium ?? 0);
                    $total = (float)($doc->total ?? 0);

                    // Resolve document date for percentage calculation
                    $docDate = $doc->$dateCol ?? $doc->created_at ?? null;
                    $rawDocType = $doc->insurance_type ?? $dt['key'] ?? 'تأمين سيارات';

                    // Resolve percentage using AgentPercentageHelper
                    $percentages = $agentStats[$agentId]['percentages'];
                    $percentage = AgentPercentageHelper::resolvePercentage($percentages, $rawDocType, $docDate);

                    $agentAmount = $premium * ((float)$percentage / 100);
                    $companyAmount = $total - $agentAmount;

                    $agentStats[$agentId]['document_count']++;
                    $agentStats[$agentId]['total_sales'] += $total;
                    $agentStats[$agentId]['agent_share'] += $agentAmount;
                    $agentStats[$agentId]['company_share'] += $companyAmount;

                    $grandTotalSales += $total;
                    $grandTotalDocs++;
                    $grandTotalAgentShare += $agentAmount;
                    $grandTotalCompanyShare += $companyAmount;
                }
            }

            // Filter and map results
            $agentResults = [];
            foreach ($agentStats as $stat) {
                if ($stat['document_count'] > 0) {
                    $agentResults[] = [
                        'id' => $stat['id'],
                        'code' => $stat['code'],
                        'agency_name' => $stat['agency_name'],
                        'agent_name' => $stat['agent_name'],
                        'document_count' => $stat['document_count'],
                        'total_sales' => round($stat['total_sales'], 2),
                        'agent_share' => round($stat['agent_share'], 2),
                        'company_share' => round($stat['company_share'], 2),
                    ];
                }
            }

            // Sort by total_sales descending
            usort($agentResults, function ($a, $b) {
                return $b['total_sales'] <=> $a['total_sales'];
            });

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_sales' => round($grandTotalSales, 2),
                    'total_documents' => $grandTotalDocs,
                    'total_agent_share' => round($grandTotalAgentShare, 2),
                    'total_company_share' => round($grandTotalCompanyShare, 2),
                    'agents_count' => count($agentResults),
                ],
                'agents' => $agentResults,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب تقرير الإنتاجية',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
