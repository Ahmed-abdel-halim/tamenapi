<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;

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
}
