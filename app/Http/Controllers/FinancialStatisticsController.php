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

        // Calculate total actual paid amount from agents across all payment sources
        $totalPaid = 0.0;
        if (DB::getSchemaBuilder()->hasTable('branches_agents')) {
            $agents = DB::table('branches_agents')->select('id')->get();
            foreach ($agents as $ag) {
                $totalPaid += \App\Helpers\AgentPaymentHelper::getTotalPaid((int)$ag->id);
            }
        }

        return response()->json([
            'stats' => [
                ['label' => 'إجمالي الإيرادات', 'value' => (float) $totalRevenue, 'icon' => 'fa-solid fa-money-bill-trend-up', 'color' => '#139625', 'trend' => $growthRate >= 0 ? 'up' : 'down', 'trendValue' => (int) abs($growthRate), 'suffix' => 'د.ل'],
                ['label' => 'إجمالي المقبوضات الفعلية', 'value' => (float) $totalPaid, 'icon' => 'fa-solid fa-hand-holding-dollar', 'color' => '#10b981', 'trend' => 'up', 'trendValue' => 10, 'suffix' => 'د.ل'],
                ['label' => 'صافي الربح', 'value' => (float) $netProfit, 'icon' => 'fa-solid fa-wallet', 'color' => '#014cb1', 'trend' => 'up', 'trendValue' => 15, 'suffix' => 'د.ل'],
                ['label' => 'إجمالي مرتبات الموظفين', 'value' => (float) $totalSalaries, 'icon' => 'fa-solid fa-users-gear', 'color' => '#f59e0b', 'trend' => 'up', 'trendValue' => 2, 'suffix' => 'د.ل'],
                ['label' => 'معدل النمو الشهري', 'value' => (float) $growthRate, 'icon' => 'fa-solid fa-chart-line', 'color' => '#8b5cf6', 'trend' => $growthRate >= 0 ? 'up' : 'down', 'trendValue' => (int) abs($growthRate), 'suffix' => '%'],
                ['label' => 'الوثائق الملغاة', 'value' => (int) $canceledDocs, 'icon' => 'fa-solid fa-file-circle-xmark', 'color' => '#ef4444', 'trend' => 'down', 'trendValue' => 3, 'suffix' => 'وثيقة'],
                ['label' => 'إجمالي الضرائب والرسوم', 'value' => (float) ($totalTax + $totalStamp + $totalSupervision), 'icon' => 'fa-solid fa-landmark', 'color' => '#ec4899', 'trend' => 'up', 'trendValue' => 12, 'suffix' => 'د.ل'],
                ['label' => 'المصروفات الثابة', 'value' => (float) $totalExpenses, 'icon' => 'fa-solid fa-building-columns', 'color' => '#6366f1', 'trend' => 'down', 'trendValue' => 1, 'suffix' => 'د.ل'],
                ['label' => 'أرصدة قيد التحصيل', 'value' => (float) max(0, $totalRevenue - $totalPaid), 'icon' => 'fa-solid fa-clock-rotate-left', 'color' => '#f59e0b', 'trend' => 'up', 'trendValue' => 20, 'suffix' => 'د.ل'],
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
    /**
     * Agent Monthly Ledger (كشف حساب الوكيل الشهري)
     * Returns monthly production breakdown per agent since contract date,
     * with carried-over balances and payment records.
     */
    public function getAgentMonthlyLedger(Request $request)
    {
        try {
            $agentId = $request->get('agent_id');
            $excludeCanceled = $request->boolean('exclude_canceled', false);
            $documentType = $request->get('document_type', 'all');

            if (!$agentId) {
                return response()->json(['success' => false, 'message' => 'يرجى تحديد الوكيل'], 422);
            }

            $agent = DB::table('branches_agents')
                ->where('id', $agentId)
                ->first();

            if (!$agent) {
                return response()->json(['success' => false, 'message' => 'الوكيل غير موجود'], 404);
            }

            // Check for agency cancellation or contract end date
            $cancellation = DB::table('agency_cancellations')
                ->where('branch_agent_id', $agentId)
                ->whereIn('status', ['approved', 'pending'])
                ->orderBy('cancellation_date', 'desc')
                ->first();

            if (!$cancellation) {
                $cancellation = DB::table('agency_cancellations')
                    ->where('branch_agent_id', $agentId)
                    ->orderBy('cancellation_date', 'desc')
                    ->first();
            }

            $cancellationDate = null;
            if ($cancellation && !empty($cancellation->cancellation_date)) {
                $cancellationDate = $cancellation->cancellation_date;
            } elseif (!empty($agent->contract_end_date)) {
                $cancellationDate = $agent->contract_end_date;
            }

            // Check if agent was renewed or is currently active with extended/no end date
            $isCurrentlyActive = (isset($agent->status) && in_array($agent->status, ['نشط', 'active']));
            $isRenewed = false;

            if ($isCurrentlyActive) {
                // If agent is active and renewal_date is set after cancellation_date, or contract_end_date is future/null
                if (!empty($agent->renewal_date) && $cancellationDate && $agent->renewal_date >= $cancellationDate) {
                    $isRenewed = true;
                } elseif (empty($agent->contract_end_date) || $agent->contract_end_date >= \Carbon\Carbon::today()->format('Y-m-d')) {
                    $isRenewed = true;
                }
            }

            if ($isRenewed) {
                if (!empty($agent->contract_end_date) && $agent->contract_end_date < \Carbon\Carbon::today()->format('Y-m-d')) {
                    $cancellationDate = $agent->contract_end_date;
                } else {
                    $cancellationDate = null; // Agent is active and renewed, don't cap by past cancellation date
                }
            }
            $schema = DB::getSchemaBuilder();

            $documentTables = [
                ['table' => 'insurance_documents',                         'date_col' => 'issue_date',  'key' => 'تأمين سيارات'],
                ['table' => 'international_insurance_documents',           'date_col' => 'issue_date',  'key' => 'تأمين سيارات دولي'],
                ['table' => 'travel_insurance_documents',                  'date_col' => 'issue_date',  'key' => 'تأمين المسافرين'],
                ['table' => 'resident_insurance_documents',                'date_col' => 'issue_date',  'key' => 'تأمين الوافدين'],
                ['table' => 'marine_structure_insurance_documents',        'date_col' => 'issue_date',  'key' => 'تأمين الهياكل البحرية'],
                ['table' => 'professional_liability_insurance_documents',  'date_col' => 'issue_date',  'key' => 'تأمين المسؤولية المهنية (الطبية)'],
                ['table' => 'personal_accident_insurance_documents',       'date_col' => 'issue_date',  'key' => 'تأمين الحوادث الشخصية'],
                ['table' => 'school_student_insurance_documents',          'date_col' => 'start_date',  'key' => 'تأمين طلبة المدارس'],
                ['table' => 'cargo_insurance_documents',                   'date_col' => 'created_at',  'key' => 'تأمين البضائع'],
                ['table' => 'cash_in_transit_insurance_documents',         'date_col' => 'start_date',  'key' => 'تأمين نقل النقدية'],
            ];

            // البحث عن تاريخ أول وثيقة وتاريخ آخر وثيقة للوكيل عبر جميع الجداول
            $firstDocDate = null;
            $lastDocDate = null;

            foreach ($documentTables as $dt) {
                $tableName = $dt['table'];
                if (!$schema->hasTable($tableName) || !$schema->hasColumn($tableName, 'branch_agent_id')) continue;

                $dateCol = $schema->hasColumn($tableName, 'issue_date') ? 'issue_date' :
                          ($schema->hasColumn($tableName, 'start_date') ? 'start_date' : 'created_at');

                $minD = DB::table($tableName)->where('branch_agent_id', $agentId)->min($dateCol);
                $maxD = DB::table($tableName)->where('branch_agent_id', $agentId)->max($dateCol);

                if ($minD && (!$firstDocDate || $minD < $firstDocDate)) {
                    $firstDocDate = $minD;
                }
                if ($maxD && (!$lastDocDate || $maxD > $lastDocDate)) {
                    $lastDocDate = $maxD;
                }
            }

            // تحديد شهر البداية (من تاريخ أول وثيقة أصدرها الوكيل، أو تاريخ التعاقد إذا لم تكن هناك وثائق)
            if ($firstDocDate) {
                $startDate = \Carbon\Carbon::parse($firstDocDate)->startOfMonth();
            } else {
                $startDateRaw = $agent->contract_date ?? $agent->created_at;
                $startDate = \Carbon\Carbon::parse($startDateRaw)->startOfMonth();
            }

            // تحديد شهر النهاية (لغاية ما وقف شغل الوكيل - تاريخ آخر وثيقة أو تاريخ الإلغاء)
            $showAllMonths = $request->boolean('show_all_months', false);
            if ($cancellationDate) {
                try {
                    $endDate = \Carbon\Carbon::parse($cancellationDate)->startOfMonth();
                } catch (\Exception $e) {
                    $endDate = \Carbon\Carbon::now()->startOfMonth();
                }
            } elseif ($lastDocDate && !$showAllMonths) {
                // التوقف عند آخر شهر أصدر فيه الوكيل وثائق
                $endDate = \Carbon\Carbon::parse($lastDocDate)->startOfMonth();
            } else {
                $endDate = \Carbon\Carbon::now()->startOfMonth();
            }

            // التأكد من أن شهر البداية لا يتجاوز شهر النهاية
            if ($startDate > $endDate) {
                $endDate = $startDate->copy();
            }

            $percentages = is_string($agent->document_percentages)
                ? json_decode($agent->document_percentages, true) ?? []
                : (is_array($agent->document_percentages) ? $agent->document_percentages : []);

            // Self-heal: populate missing year/month in monthly_account_closures from from_date, avoiding duplicate key errors
            $legacyClosures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->where(function ($q) {
                    $q->whereNull('year')->orWhereNull('month');
                })
                ->whereNotNull('from_date')
                ->get();

            foreach ($legacyClosures as $lc) {
                try {
                    $d = \Carbon\Carbon::parse($lc->from_date);
                    $exists = DB::table('monthly_account_closures')
                        ->where('branch_agent_id', $agentId)
                        ->where('year', $d->year)
                        ->where('month', $d->month)
                        ->where('id', '!=', $lc->id)
                        ->exists();

                    if ($exists) {
                        DB::table('monthly_account_closures')->where('id', $lc->id)->delete();
                    } else {
                        DB::table('monthly_account_closures')
                            ->where('id', $lc->id)
                            ->update(['year' => $d->year, 'month' => $d->month]);
                    }
                } catch (\Exception $e) {}
            }

            // Load existing closures for this agent (keyed by YYYY-MM)
            $existingClosures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->get()
                ->keyBy(function ($row) {
                    $y = $row->year;
                    $m = $row->month;
                    if ((!$y || !$m) && !empty($row->from_date)) {
                        try {
                            $dt = \Carbon\Carbon::parse($row->from_date);
                            $y = $dt->year;
                            $m = $dt->month;
                        } catch (\Exception $e) {}
                    }
                    if ($y && $m) {
                        return $y . '-' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    }
                    return null;
                });

            // Build month list and collect production data per month
            $months = [];
            $cursor = $startDate->copy();
            while ($cursor <= $endDate) {
                $monthNum = (int)$cursor->month;
                $yearNum  = (int)$cursor->year;
                $months[$cursor->format('Y-m')] = [
                    'year'           => $yearNum,
                    'month'          => $monthNum,
                    'month_label'    => "شهر {$monthNum} - {$yearNum}",
                    'month_key'      => $cursor->format('Y-m'),
                    'from_date'      => $cursor->format('Y-m-01'),
                    'to_date'        => $cursor->copy()->endOfMonth()->format('Y-m-d'),
                    'document_count' => 0,
                    'active_count'   => 0,
                    'expired_count'  => 0,
                    'canceled_count' => 0,
                    'total_sales'    => 0.0,
                    'agent_share'    => 0.0,
                    'company_share'  => 0.0,
                    'percentage'     => 0.0,
                ];
                $cursor->addMonth();
            }

            $todayStr = \Carbon\Carbon::today()->format('Y-m-d');

            // Fetch all docs for this agent across all tables
            foreach ($documentTables as $dt) {
                $tableName = $dt['table'];

                // تصفية بحسب نوع الوثيقة
                if ($documentType && $documentType !== 'all') {
                    if ($documentType !== $tableName && $documentType !== $dt['key']) {
                        continue;
                    }
                }

                if (!$schema->hasTable($tableName)) continue;
                if (!$schema->hasColumn($tableName, 'branch_agent_id')) continue;

                // تحديد أعمدة التاريخ المتاحة لهذا الجدول (مرة واحدة قبل الحلقة)
                // الأولوية: issue_date > start_date > created_at
                $hasIssueDate = $schema->hasColumn($tableName, 'issue_date');
                $hasStartDate = $schema->hasColumn($tableName, 'start_date');

                $query = DB::table($tableName)->where('branch_agent_id', $agentId);

                // Exclude canceled documents if requested
                if ($excludeCanceled && $schema->hasColumn($tableName, 'status')) {
                    $query->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'ملغية');
                    });
                }

                $docs = $query->get();

                foreach ($docs as $doc) {
                    // تحديد تاريخ الوثيقة بالأولوية: issue_date > start_date > created_at
                    // نستخدم تاريخ البداية الفعلي للوثيقة وليس تاريخ إدخالها في النظام
                    $rawDate = null;
                    if ($hasIssueDate && !empty($doc->issue_date)) {
                        $rawDate = $doc->issue_date;
                    } elseif ($hasStartDate && !empty($doc->start_date)) {
                        $rawDate = $doc->start_date;
                    } else {
                        $rawDate = $doc->created_at ?? null;
                    }

                    if (!$rawDate) continue;

                    try {
                        $docDate = \Carbon\Carbon::parse($rawDate);
                    } catch (\Exception $e) {
                        continue;
                    }

                    $monthKey = $docDate->format('Y-m');
                    if (!isset($months[$monthKey])) {
                        // نقبل أي شهر لا يتجاوز الشهر الحالي (بما يشمل الوثائق القديمة التي قد تكون قبل startDate)
                        if ($docDate->copy()->startOfMonth() <= \Carbon\Carbon::now()->startOfMonth()) {
                            $mNum = (int)$docDate->month;
                            $yNum = (int)$docDate->year;
                            $months[$monthKey] = [
                                'year'           => $yNum,
                                'month'          => $mNum,
                                'month_label'    => "شهر {$mNum} - {$yNum}",
                                'month_key'      => $monthKey,
                                'from_date'      => $docDate->copy()->startOfMonth()->format('Y-m-d'),
                                'to_date'        => $docDate->copy()->endOfMonth()->format('Y-m-d'),
                                'document_count' => 0,
                                'active_count'   => 0,
                                'expired_count'  => 0,
                                'canceled_count' => 0,
                                'total_sales'    => 0.0,
                                'agent_share'    => 0.0,
                                'company_share'  => 0.0,
                                'percentage'     => 0.0,
                            ];
                            ksort($months);
                        } else {
                            continue;
                        }
                    }

                    $premium  = (float)($doc->premium ?? 0);
                    $total    = (float)($doc->total ?? 0);
                    $rawDocType = $doc->insurance_type ?? $dt['key'] ?? 'تأمين سيارات';

                    $pct          = \App\Helpers\AgentPercentageHelper::resolvePercentage($percentages, $rawDocType, $rawDate);
                    $agentAmount  = $premium * ($pct / 100);
                    $companyAmount = $total - $agentAmount;

                    // Check cancellation status
                    $isCanceled = false;
                    if (isset($doc->is_canceled) && $doc->is_canceled) {
                        $isCanceled = true;
                    } elseif (isset($doc->canceled_at) && $doc->canceled_at !== null) {
                        $isCanceled = true;
                    } elseif (isset($doc->status) && in_array(mb_strtolower(trim($doc->status)), ['ملغية', 'ملغيه', 'canceled', 'cancelled'])) {
                        $isCanceled = true;
                    }

                    $months[$monthKey]['document_count']++;

                    if ($isCanceled) {
                        $months[$monthKey]['canceled_count']++;
                        // Canceled documents DO NOT contribute to sales or agent/company shares!
                    } else {
                        $isExpired = false;
                        if (!empty($doc->end_date) && \Carbon\Carbon::parse($doc->end_date)->format('Y-m-d') < $todayStr) {
                            $isExpired = true;
                        } elseif (isset($doc->status) && in_array(mb_strtolower(trim($doc->status)), ['منتهية', 'منتهيه', 'expired'])) {
                            $isExpired = true;
                        }

                        if ($isExpired) {
                            $months[$monthKey]['expired_count']++;
                        } else {
                            $months[$monthKey]['active_count']++;
                        }

                        $months[$monthKey]['total_sales']   += $total;
                        $months[$monthKey]['agent_share']   += $agentAmount;
                        $months[$monthKey]['company_share'] += $companyAmount;
                    }

                    // Store last resolved percentage for display
                    if ($months[$monthKey]['document_count'] === 1) {
                        $months[$monthKey]['percentage'] = $pct;
                    }
                }
            }

            // Collect all payment sources for this agent (Payment Vouchers, Approved Transfers, Closures) via unified helper
            $allPayments = \App\Helpers\AgentPaymentHelper::getAllPayments((int)$agentId);

            // Group payments by month_key
            $firstMonthKey = array_key_first($months);
            $lastMonthKey  = array_key_last($months);
            $paymentsByMonth = [];

            foreach ($allPayments as $p) {
                $mk = $p['month_key'];
                if ($mk && isset($months[$mk])) {
                    $paymentsByMonth[$mk] = ($paymentsByMonth[$mk] ?? 0.0) + $p['amount'];
                }
            }

            // Build final rows with carried-over balance
            $carriedBalance = 0.0;
            $rows = [];
            $grandTotalSales        = 0.0;
            $grandTotalDocs         = 0;
            $grandTotalActiveDocs   = 0;
            $grandTotalExpiredDocs  = 0;
            $grandTotalCanceledDocs = 0;
            $grandTotalAgentShare   = 0.0;
            $grandTotalCompanyShare = 0.0;
            $grandTotalPaid         = 0.0;
            $grandTotalRemaining    = 0.0;

            foreach ($months as $mk => $m) {
                $closure = $existingClosures->get($mk);

                $agentShare   = round($m['agent_share'], 2);
                $companyShare = round($m['company_share'], 2);
                $dueAmount    = $companyShare;

                // Use combined payments for this month (fallback to closure paid_amount)
                if (isset($paymentsByMonth[$mk])) {
                    $paidAmount = round($paymentsByMonth[$mk], 2);
                } else {
                    $paidAmount = $closure ? (float)$closure->paid_amount : 0.0;
                }

                $remaining = round($dueAmount + $carriedBalance - $paidAmount, 2);
                $closureId = $closure ? $closure->id : null;

                $rows[] = [
                    'closure_id'      => $closureId,
                    'year'            => $m['year'],
                    'month'           => $m['month'],
                    'month_label'     => $m['month_label'],
                    'month_key'       => $mk,
                    'from_date'       => $m['from_date'],
                    'to_date'         => $m['to_date'],
                    'percentage'      => round($m['percentage'], 2),
                    'document_count'  => $m['document_count'],
                    'active_count'    => $m['active_count'],
                    'expired_count'   => $m['expired_count'],
                    'canceled_count'  => $m['canceled_count'],
                    'total_sales'     => round($m['total_sales'], 2),
                    'agent_share'     => $agentShare,
                    'company_share'   => $companyShare,
                    'carried_balance' => round($carriedBalance, 2),
                    'paid_amount'     => round($paidAmount, 2),
                    'remaining'       => $remaining,
                    'notes'           => $closure->notes ?? null,
                    'is_audited'      => $closure ? (bool)($closure->is_audited ?? false) : false,
                ];

                $carriedBalance = $remaining > 0 ? $remaining : 0.0;

                $grandTotalSales        += $m['total_sales'];
                $grandTotalDocs         += $m['document_count'];
                $grandTotalActiveDocs   += $m['active_count'];
                $grandTotalExpiredDocs  += $m['expired_count'];
                $grandTotalCanceledDocs += $m['canceled_count'];
                $grandTotalAgentShare   += $agentShare;
                $grandTotalCompanyShare += $companyShare;
                $grandTotalPaid         += $paidAmount;
                $grandTotalRemaining    = $carriedBalance;
            }

            return response()->json([
                'success' => true,
                'agent' => [
                    'id'                => $agent->id,
                    'code'              => $agent->code,
                    'agency_name'       => $agent->agency_name,
                    'agent_name'        => $agent->agent_name,
                    'contract_date'     => $agent->contract_date ?? ($firstDocDate ? substr($firstDocDate, 0, 10) : null),
                    'first_doc_date'    => $firstDocDate ? substr($firstDocDate, 0, 10) : null,
                    'last_doc_date'     => $lastDocDate ? substr($lastDocDate, 0, 10) : null,
                    'contract_end_date' => $cancellationDate ?? $agent->contract_end_date ?? null,
                    'status'            => $agent->status ?? null,
                    'notes'             => $agent->notes ?? null,
                    'is_audited'        => (bool)($agent->is_audited ?? false),
                ],
                'months' => array_values($rows),
                'summary' => [
                    'total_months'        => count($rows),
                    'total_documents'     => $grandTotalDocs,
                    'active_documents'    => $grandTotalActiveDocs,
                    'expired_documents'   => $grandTotalExpiredDocs,
                    'canceled_documents'  => $grandTotalCanceledDocs,
                    'total_sales'         => round($grandTotalSales, 2),
                    'total_agent_share'   => round($grandTotalAgentShare, 2),
                    'total_company_share' => round($grandTotalCompanyShare, 2),
                    'total_paid'          => round($grandTotalPaid, 2),
                    'total_remaining'     => round($grandTotalRemaining, 2),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب كشف الحساب الشهري',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Toggle monthly audit status for a specific month of an agent
     */
    public function toggleMonthlyAudit(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_agent_id' => 'required|integer|exists:branches_agents,id',
                'year'            => 'required|integer',
                'month'           => 'required|integer|min:1|max:12',
                'is_audited'      => 'nullable|boolean',
            ]);

            $user = $request->user() ?? auth('sanctum')->user() ?? auth()->user();
            if ($user && !$user->is_admin) {
                $authDocs = is_array($user->authorized_documents ?? null)
                    ? $user->authorized_documents
                    : (is_string($user->authorized_documents ?? null) ? json_decode($user->authorized_documents, true) : []);

                $canAudit = in_array('مدير الوكلاء', $authDocs) ||
                            in_array('تدقيق كشف حساب الوكيل', $authDocs) ||
                            in_array('إدارة الفروع والوكلاء', $authDocs) ||
                            in_array('إدارة الوكلاء', $authDocs) ||
                            in_array('إدارة الوكيل', $authDocs);

                if (!$canAudit) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك باعتماد أو تدقيق حسابات الوكيل'
                    ], 403);
                }
            }

            $fromDate = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->format('Y-m-d');
            $toDate   = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth()->format('Y-m-d');
            $monthPrefix = $validated['year'] . '-' . sprintf('%02d', $validated['month']);

            $closure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->first();

            if (!$closure) {
                $closure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                    ->where(function ($q) use ($fromDate, $monthPrefix) {
                        $q->where('from_date', $fromDate)
                          ->orWhere('from_date', 'like', "{$monthPrefix}-%");
                    })
                    ->first();
            }

            // Remove any duplicate legacy rows for this month
            if ($closure) {
                \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                    ->where('id', '!=', $closure->id)
                    ->where(function ($q) use ($validated, $fromDate, $monthPrefix) {
                        $q->where(function ($q2) use ($validated) {
                            $q2->where('year', $validated['year'])
                               ->where('month', $validated['month']);
                        })
                        ->orWhere('from_date', $fromDate)
                        ->orWhere('from_date', 'like', "{$monthPrefix}-%");
                    })
                    ->delete();
            }

            if (!$closure) {
                $closure = new \App\Models\MonthlyAccountClosure();
                $closure->branch_agent_id = $validated['branch_agent_id'];
                $closure->due_amount       = 0;
                $closure->paid_amount      = 0;
                $closure->remaining_amount = 0;
            }

            $closure->year       = $validated['year'];
            $closure->month      = $validated['month'];
            $closure->from_date  = $fromDate;
            $closure->to_date    = $toDate;

            $newState = isset($validated['is_audited'])
                ? (bool)$validated['is_audited']
                : !$closure->is_audited;

            $closure->is_audited = $newState;
            $closure->save();

            return response()->json([
                'success'    => true,
                'message'    => $newState ? 'تم تدقيق حساب هذا الشهر بنجاح' : 'تم تغيير حالة هذا الشهر إلى لم يتم التدقيق',
                'is_audited' => $newState,
                'year'       => $validated['year'],
                'month'      => $validated['month'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث حالة التدقيق للشهر',
                'error'   => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Update monthly payment (تسديد الدفعة الشهرية مع إنشاء إيصال القبض وإصدار معاملة الخزينة)
     */
    public function updateMonthlyPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_agent_id'    => 'required|integer|exists:branches_agents,id',
                'year'               => 'required|integer',
                'month'              => 'required|integer|min:1|max:12',
                'paid_amount'        => 'required|numeric|min:0',
                'due_amount'         => 'required|numeric|min:0',
                'payment_amount'     => 'nullable|numeric|min:0',
                'payment_method'     => 'nullable|string|max:100',
                'bank_name'          => 'nullable|string|max:150',
                'reference_number'   => 'nullable|string|max:150',
                'payment_date'       => 'nullable|date',
                'voucher_number'     => 'nullable|string|max:100',
                'notes'              => 'nullable|string|max:500',
                'pos_machine_id'     => 'nullable|integer|exists:pos_machines,id',
                'transactions_count' => 'nullable|integer|min:1',
                'report_file'        => 'nullable|file|mimes:pdf,xlsx,xls,csv,jpg,jpeg,png,webp|max:20480',
            ]);

            $user = $request->user() ?? auth('sanctum')->user() ?? auth()->user();
            if ($user && !$user->is_admin) {
                $authDocs = is_array($user->authorized_documents ?? null)
                    ? $user->authorized_documents
                    : (is_string($user->authorized_documents ?? null) ? json_decode($user->authorized_documents, true) : []);

                $canPay = in_array('تسديد كشف حساب الوكيل', $authDocs) ||
                          in_array('مدير الوكلاء', $authDocs) ||
                          in_array('المحاسب المالي', $authDocs) ||
                          in_array('إدارة الفروع والوكلاء', $authDocs) ||
                          in_array('إدارة الوكلاء', $authDocs) ||
                          in_array('إدارة الوكيل', $authDocs);

                if (!$canPay) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح لك بتسديد حسابات الوكيل'
                    ], 403);
                }

                $canManageAgent = in_array('مدير الوكلاء', $authDocs) ||
                                  in_array('إدارة الفروع والوكلاء', $authDocs) ||
                                  in_array('إدارة الوكلاء', $authDocs) ||
                                  in_array('إدارة الوكيل', $authDocs);

                $isAudited = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                    ->where('year', $validated['year'])
                    ->where('month', $validated['month'])
                    ->where('is_audited', true)
                    ->exists();

                if ($isAudited && !$canManageAgent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'هذا الشهر مدقق، لا يمكن التسديد إلا بعد إلغاء التدقيق من مدير الوكلاء'
                    ], 403);
                }
            }

            $fromDate = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->format('Y-m-d');
            $toDate   = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth()->format('Y-m-d');
            $monthPrefix = $validated['year'] . '-' . sprintf('%02d', $validated['month']);
            $remaining = round((float)$validated['due_amount'] - (float)$validated['paid_amount'], 2);

            $existingClosure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->first();

            if (!$existingClosure) {
                $existingClosure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                    ->where(function ($q) use ($fromDate, $monthPrefix) {
                        $q->where('from_date', $fromDate)
                          ->orWhere('from_date', 'like', "{$monthPrefix}-%");
                    })
                    ->first();
            }

            // Clean up any remaining duplicate legacy closure rows for this month
            if ($existingClosure) {
                \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                    ->where('id', '!=', $existingClosure->id)
                    ->where(function ($q) use ($validated, $fromDate, $monthPrefix) {
                        $q->where(function ($q2) use ($validated) {
                            $q2->where('year', $validated['year'])
                               ->where('month', $validated['month']);
                        })
                        ->orWhere('from_date', $fromDate)
                        ->orWhere('from_date', 'like', "{$monthPrefix}-%");
                    })
                    ->delete();
            }

            $previousPaidAmount = $existingClosure ? (float)$existingClosure->paid_amount : 0;

            if (!$existingClosure) {
                $closure = new \App\Models\MonthlyAccountClosure();
                $closure->branch_agent_id = $validated['branch_agent_id'];
                $closure->documents_data   = [];
                $closure->is_audited       = false;
            } else {
                $closure = $existingClosure;
            }

            $closure->year             = $validated['year'];
            $closure->month            = $validated['month'];
            $closure->from_date        = $fromDate;
            $closure->to_date          = $toDate;
            $closure->due_amount       = $validated['due_amount'];
            $closure->paid_amount      = $validated['paid_amount'];
            $closure->remaining_amount = max(0, $remaining);
            $closure->notes            = $validated['notes'] ?? $closure->notes;
            if ($closure->documents_data === null) {
                $closure->documents_data = [];
            }
            $closure->save();

            // Amount paid in this specific action
            $newPaymentAmount = isset($validated['payment_amount']) && (float)$validated['payment_amount'] > 0
                ? (float)$validated['payment_amount']
                : round((float)$validated['paid_amount'] - $previousPaidAmount, 2);

            $paymentVoucher = null;
            $posTransaction = null;
            if ($newPaymentAmount > 0) {
                try {
                    $agent = \App\Models\BranchAgent::find($validated['branch_agent_id']);
                    $agencyName = $agent ? ($agent->agency_name ?? ($agent->agent_name ?? "وكيل #{$agent->id}")) : 'وكيل';

                    $monthNames = [1=>'يناير', 2=>'فبراير', 3=>'مارس', 4=>'أبريل', 5=>'مايو', 6=>'يونيو', 7=>'يوليو', 8=>'أغسطس', 9=>'سبتمبر', 10=>'أكتوبر', 11=>'نوفمبر', 12=>'ديسمبر'];
                    $monthLabel = ($monthNames[$validated['month']] ?? $validated['month']) . ' ' . $validated['year'];

                    $voucherNumber = !empty($validated['voucher_number'])
                        ? $validated['voucher_number']
                        : ('PV-' . date('Y') . '-' . rand(1000, 9999));

                    while (\Illuminate\Support\Facades\Schema::hasTable('payment_vouchers') && \App\Models\PaymentVoucher::where('voucher_number', $voucherNumber)->exists()) {
                        $voucherNumber = 'PV-' . date('Y') . '-' . rand(1000, 9999);
                    }

                    $voucherNotes = "تسديد دفعة كشف حساب شهري ({$monthLabel})" . (!empty($validated['notes']) ? " - {$validated['notes']}" : '');
                    $paymentMethod = $validated['payment_method'] ?? 'نقدي';
                    $paymentDate = $validated['payment_date'] ?? date('Y-m-d');
                    $bankName = $validated['bank_name'] ?? null;
                    $refNumber = $validated['reference_number'] ?? null;

                    // Handle file upload if provided
                    $filePath = null;
                    if ($request->hasFile('report_file')) {
                        $filePath = $request->file('report_file')->store('pos_reports', 'public');
                    }

                    // Handle POS Transaction settlement creation if POS machine is specified
                    $posMachine = null;
                    if (!empty($validated['pos_machine_id']) && \Illuminate\Support\Facades\Schema::hasTable('pos_transactions')) {
                        $posMachine = \App\Models\PosMachine::find($validated['pos_machine_id']);
                        if ($posMachine && empty($bankName)) {
                            $bankName = $posMachine->bank_name;
                        }

                        $txnCount = !empty($request->input('transactions_count')) ? (int)$request->input('transactions_count') : 1;
                        $posTransaction = \App\Models\PosTransaction::create([
                            'pos_machine_id'     => $validated['pos_machine_id'],
                            'transaction_date'   => $paymentDate,
                            'amount'             => $newPaymentAmount,
                            'transactions_count' => $txnCount,
                            'reference_number'   => $refNumber,
                            'report_file'        => $filePath,
                            'is_reconciled'      => false,
                            'notes'              => !empty($validated['notes']) 
                                ? $validated['notes'] 
                                : "تسديد دفعة كشف حساب شهري ({$monthLabel}) - وكيل: {$agencyName}",
                        ]);
                    }

                    // 1. Create Single Payment Voucher (إيصال قبض مالي موحد في إدارة الإيرادات)
                    if (\Illuminate\Support\Facades\Schema::hasTable('payment_vouchers')) {
                        $paymentVoucher = \App\Models\PaymentVoucher::create([
                            'voucher_number'   => $voucherNumber,
                            'branch_agent_id'  => $validated['branch_agent_id'],
                            'amount'           => $newPaymentAmount,
                            'payment_method'   => $paymentMethod,
                            'bank_name'        => $bankName,
                            'reference_number' => $refNumber,
                            'payment_date'     => $paymentDate,
                            'notes'            => mb_substr($voucherNotes, 0, 490),
                            'extra_details'    => [
                                'type'               => 'monthly_account_closure',
                                'year'               => $validated['year'],
                                'month'              => $validated['month'],
                                'closure_id'         => $closure->id,
                                'pos_machine_id'     => $validated['pos_machine_id'] ?? null,
                                'pos_machine_name'   => $posMachine?->machine_name ?? null,
                                'pos_transaction_id' => $posTransaction?->id ?? null,
                                'report_file'        => $filePath ?? null,
                            ]
                        ]);
                    }

                    // 2. Create Treasury Transaction (معاملة مقبوضات واحدة في خزينة الإيرادات)
                    if (\Illuminate\Support\Facades\Schema::hasTable('treasury_transactions')) {
                        $desc = "تسديد كشف حساب شهري - {$agencyName} - شهر {$monthLabel}";
                        if ($posMachine) {
                            $desc .= " (POS: {$posMachine->machine_name})";
                        }
                        \App\Models\TreasuryTransaction::create([
                            'transaction_date' => $paymentDate,
                            'type'             => 'income',
                            'amount'           => $newPaymentAmount,
                            'description'      => mb_substr($desc, 0, 190),
                            'source'           => mb_substr($agencyName, 0, 190),
                            'reference_number' => $refNumber ?: $voucherNumber,
                            'branch_agent_id'  => $validated['branch_agent_id'],
                            'payment_source'   => $paymentMethod,
                            'notes'            => !empty($validated['notes']) ? mb_substr($validated['notes'], 0, 490) : null,
                        ]);
                    }
                } catch (\Exception $ex) {
                    \Illuminate\Support\Facades\Log::warning('Payment voucher creation warning: ' . $ex->getMessage());
                }
            }

            return response()->json([
                'success'         => true,
                'message'         => 'تم تسجيل الدفعة وإنشاء إيصال القبض في إدارة الإيرادات والخزينة بنجاح' . ($posTransaction ? ' وتسجيل تسوية مبيعات POS' : ''),
                'closure'         => $closure,
                'payment_voucher' => $paymentVoucher,
                'pos_transaction' => $posTransaction,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error updating monthly payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدفعة: ' . $e->getMessage(),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset/Cancel monthly payment for an agent (إلغاء/تصفير المستلم لهذا الشهر وإلغاء إيصالات القبض والمعاملات المرتبطة)
     */
    public function resetMonthlyPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_agent_id' => 'required|integer|exists:branches_agents,id',
                'year'            => 'required|integer',
                'month'           => 'required|integer|min:1|max:12',
            ]);

            $agentId = (int)$validated['branch_agent_id'];
            $year    = (int)$validated['year'];
            $month   = (int)$validated['month'];

            $user = $request->user() ?? auth('sanctum')->user() ?? auth()->user();
            if ($user && !$user->is_admin) {
                $authDocs = is_array($user->authorized_documents ?? null)
                    ? $user->authorized_documents
                    : (is_string($user->authorized_documents ?? null) ? json_decode($user->authorized_documents, true) : []);

                $canManageAgent = in_array('مدير الوكلاء', $authDocs) ||
                                  in_array('إدارة الفروع والوكلاء', $authDocs) ||
                                  in_array('إدارة الوكلاء', $authDocs) ||
                                  in_array('إدارة الوكيل', $authDocs);

                $now = \Carbon\Carbon::now();
                $isPastMonth = ($year < (int)$now->year) || ($year === (int)$now->year && $month < (int)$now->month);

                $isAudited = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $agentId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('is_audited', true)
                    ->exists();

                if (($isAudited || $isPastMonth) && !$canManageAgent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح للمحاسب بتعديل أو إلغاء استلامات الشهور السابقة أو المدققة، يلزم صلاحية مدير الوكلاء'
                    ], 403);
                }
            }

            // 1. Find and reset all matching MonthlyAccountClosure records
            $fromDate = \Carbon\Carbon::create($year, $month, 1)->format('Y-m-d');
            $monthPrefix = sprintf('%04d-%02d', $year, $month);

            $closures = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $agentId)
                ->where(function ($q) use ($year, $month, $fromDate, $monthPrefix) {
                    $q->where(function ($q2) use ($year, $month) {
                        $q2->where('year', $year)
                           ->where('month', $month);
                    })
                    ->orWhere('from_date', $fromDate)
                    ->orWhere('from_date', 'like', "{$monthPrefix}-%");
                })
                ->get();

            $closure = $closures->first();

            foreach ($closures as $c) {
                $c->year = $year;
                $c->month = $month;
                $c->paid_amount = 0;
                $c->remaining_amount = $c->due_amount;
                $c->save();
            }

            // 2. Find and delete associated PaymentVouchers and TreasuryTransactions
            $monthNames = [1=>'يناير', 2=>'فبراير', 3=>'مارس', 4=>'أبريل', 5=>'مايو', 6=>'يونيو', 7=>'يوليو', 8=>'أغسطس', 9=>'سبتمبر', 10=>'أكتوبر', 11=>'نوفمبر', 12=>'ديسمبر'];
            $monthLabel = ($monthNames[$month] ?? $month) . ' ' . $year;

            $vouchers = \App\Models\PaymentVoucher::where('branch_agent_id', $agentId)
                ->get()
                ->filter(function ($v) use ($closure, $year, $month, $monthLabel) {
                    $extra = is_string($v->extra_details) ? json_decode($v->extra_details, true) : (array)($v->extra_details ?? []);
                    if ($closure && isset($extra['closure_id']) && (int)$extra['closure_id'] === (int)$closure->id) {
                        return true;
                    }
                    if (isset($extra['year']) && (int)$extra['year'] === $year && isset($extra['month']) && (int)$extra['month'] === $month) {
                        return true;
                    }
                    if (str_contains($v->notes ?? '', $monthLabel)) {
                        return true;
                    }
                    return false;
                });

            $voucherNumbers = [];
            foreach ($vouchers as $v) {
                $extra = is_array($v->extra_details) ? $v->extra_details : (json_decode($v->extra_details ?? '[]', true) ?: []);
                if (!empty($extra['pos_transaction_id'])) {
                    $posTxn = \App\Models\PosTransaction::find($extra['pos_transaction_id']);
                    if ($posTxn) {
                        if ($posTxn->report_file) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($posTxn->report_file);
                        }
                        $posTxn->delete();
                    }
                }
                if (!empty($extra['report_file'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($extra['report_file']);
                }
                if (!empty($v->voucher_number)) {
                    $voucherNumbers[] = $v->voucher_number;
                }
                $v->delete();
            }

            if (!empty($voucherNumbers)) {
                \App\Models\TreasuryTransaction::where('branch_agent_id', $agentId)
                    ->whereIn('reference_number', $voucherNumbers)
                    ->delete();
            }

            // Also remove direct treasury transactions for this month closure if matching description pattern
            \App\Models\TreasuryTransaction::where('branch_agent_id', $agentId)
                ->where('description', 'like', "%تسديد كشف حساب شهري%{$monthLabel}%")
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء/تصفير جميع الدفعات المسجلة وحذف إيصالات القبض لهذا الشهر بنجاح',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إلغاء التسديد',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    public function getLiveAgentsProduction(Request $request)
    {
        try {
            $fromDate = $request->get('from_date');
            $toDate = $request->get('to_date');
            $docTypeFilter = $request->get('doc_type') ?: $request->get('document_type');

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
                    'by_type' => [],
                ];
            }

            // Define document tables with their date columns and percentage keys
            $documentTables = [
                ['table' => 'insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين سيارات', 'label' => 'تأمين سيارات (إجباري وشامل)'],
                ['table' => 'international_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين سيارات دولي', 'label' => 'تأمين سيارات دولي (البطاقة البرتقالية)'],
                ['table' => 'travel_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين المسافرين', 'label' => 'تأمين المسافرين'],
                ['table' => 'resident_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الوافدين', 'label' => 'تأمين الوافدين (الإقامة)'],
                ['table' => 'marine_structure_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الهياكل البحرية', 'label' => 'تأمين الهياكل البحرية'],
                ['table' => 'professional_liability_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين المسؤولية المهنية (الطبية)', 'label' => 'تأمين المسؤولية المهنية (الطبية)'],
                ['table' => 'personal_accident_insurance_documents', 'date_col' => 'issue_date', 'fallback_date' => 'created_at', 'key' => 'تأمين الحوادث الشخصية', 'label' => 'تأمين الحوادث الشخصية'],
                ['table' => 'school_student_insurance_documents', 'date_col' => 'start_date', 'fallback_date' => 'created_at', 'key' => 'تأمين طلبة المدارس', 'label' => 'تأمين طلبة المدارس'],
                ['table' => 'cargo_insurance_documents', 'date_col' => 'created_at', 'fallback_date' => 'created_at', 'key' => 'تأمين شحن البضائع', 'label' => 'تأمين شحن البضائع'],
                ['table' => 'cash_in_transit_insurance_documents', 'date_col' => 'start_date', 'fallback_date' => 'created_at', 'key' => 'تأمين نقل النقدية', 'label' => 'تأمين نقل النقدية'],
            ];

            $grandTotalSales = 0;
            $grandTotalDocs = 0;
            $grandTotalAgentShare = 0;
            $grandTotalCompanyShare = 0;

            $typesSummary = [];
            foreach ($documentTables as $dt) {
                $typesSummary[$dt['key']] = [
                    'key' => $dt['key'],
                    'label' => $dt['label'] ?? $dt['key'],
                    'document_count' => 0,
                    'total_sales' => 0.0,
                    'agent_share' => 0.0,
                    'company_share' => 0.0,
                ];
            }

            $schema = DB::getSchemaBuilder();

            foreach ($documentTables as $dt) {
                $tableName = $dt['table'];
                $dateCol = $dt['date_col'];
                $typeKey = $dt['key'];

                // Check if table exists and has needed columns
                if (!$schema->hasTable($tableName)) continue;
                if (!$schema->hasColumn($tableName, 'branch_agent_id')) continue;

                // Apply doc_type filter if specified
                if ($docTypeFilter && $docTypeFilter !== 'all' && $docTypeFilter !== 'الكل') {
                    if ($docTypeFilter !== $typeKey && $docTypeFilter !== $tableName) {
                        continue;
                    }
                }

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

                    // Breakdown per agent by doc type
                    if (!isset($agentStats[$agentId]['by_type'][$typeKey])) {
                        $agentStats[$agentId]['by_type'][$typeKey] = [
                            'key' => $typeKey,
                            'label' => $dt['label'] ?? $typeKey,
                            'document_count' => 0,
                            'total_sales' => 0.0,
                            'agent_share' => 0.0,
                            'company_share' => 0.0,
                        ];
                    }
                    $agentStats[$agentId]['by_type'][$typeKey]['document_count']++;
                    $agentStats[$agentId]['by_type'][$typeKey]['total_sales'] += $total;
                    $agentStats[$agentId]['by_type'][$typeKey]['agent_share'] += $agentAmount;
                    $agentStats[$agentId]['by_type'][$typeKey]['company_share'] += $companyAmount;

                    // Aggregate into global types summary
                    if (isset($typesSummary[$typeKey])) {
                        $typesSummary[$typeKey]['document_count']++;
                        $typesSummary[$typeKey]['total_sales'] += $total;
                        $typesSummary[$typeKey]['agent_share'] += $agentAmount;
                        $typesSummary[$typeKey]['company_share'] += $companyAmount;
                    }

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
                    $byTypeFormatted = [];
                    foreach ($stat['by_type'] as $k => $v) {
                        $byTypeFormatted[] = [
                            'key' => $v['key'],
                            'label' => $v['label'],
                            'document_count' => $v['document_count'],
                            'total_sales' => round($v['total_sales'], 2),
                            'agent_share' => round($v['agent_share'], 2),
                            'company_share' => round($v['company_share'], 2),
                        ];
                    }
                    // Sort agent's by_type by document_count desc
                    usort($byTypeFormatted, function ($a, $b) {
                        return $b['document_count'] <=> $a['document_count'];
                    });

                    $agentResults[] = [
                        'id' => $stat['id'],
                        'code' => $stat['code'],
                        'agency_name' => $stat['agency_name'],
                        'agent_name' => $stat['agent_name'],
                        'document_count' => $stat['document_count'],
                        'total_sales' => round($stat['total_sales'], 2),
                        'agent_share' => round($stat['agent_share'], 2),
                        'company_share' => round($stat['company_share'], 2),
                        'by_type' => $byTypeFormatted,
                    ];
                }
            }

            // Sort by total_sales descending
            usort($agentResults, function ($a, $b) {
                return $b['total_sales'] <=> $a['total_sales'];
            });

            // Format types summary
            $formattedTypesSummary = [];
            foreach ($typesSummary as $k => $v) {
                $formattedTypesSummary[] = [
                    'key' => $v['key'],
                    'label' => $v['label'],
                    'document_count' => $v['document_count'],
                    'total_sales' => round($v['total_sales'], 2),
                    'agent_share' => round($v['agent_share'], 2),
                    'company_share' => round($v['company_share'], 2),
                ];
            }

            return response()->json([
                'success' => true,
                'summary' => [
                    'total_sales' => round($grandTotalSales, 2),
                    'total_documents' => $grandTotalDocs,
                    'total_agent_share' => round($grandTotalAgentShare, 2),
                    'total_company_share' => round($grandTotalCompanyShare, 2),
                    'agents_count' => count($agentResults),
                ],
                'types_summary' => $formattedTypesSummary,
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

    /**
     * Get all documents for a specific agent in a specific month across all 10 document tables.
     */
    public function getAgentMonthDocuments(Request $request)
    {
        try {
            $agentId = $request->get('agent_id');
            $year    = (int)$request->get('year');
            $month   = (int)$request->get('month');
            $search  = $request->get('search');
            $docTypeFilter = $request->get('document_type');

            if (!$agentId || !$year || !$month) {
                return response()->json(['success' => false, 'message' => 'بيانات الطلب غير مكتملة (يرجى تحديد الوكيل والسنة والشهر)'], 422);
            }

            $agent = DB::table('branches_agents')->where('id', $agentId)->first();
            if (!$agent) {
                return response()->json(['success' => false, 'message' => 'الوكيل غير موجود'], 404);
            }

            $percentages = is_string($agent->document_percentages)
                ? json_decode($agent->document_percentages, true) ?? []
                : (is_array($agent->document_percentages) ? $agent->document_percentages : []);

            $fromDate = \Carbon\Carbon::create($year, $month, 1)->startOfDay()->toDateTimeString();
            $toDate   = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfDay()->toDateTimeString();

            $documentTables = [
                'compulsory' => [
                    'table'        => 'insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين إجباري سيارات',
                ],
                'international' => [
                    'table'        => 'international_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'document_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين السيارات الدولي',
                ],
                'travel' => [
                    'table'        => 'travel_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين المسافرين',
                ],
                'resident' => [
                    'table'        => 'resident_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين الوافدين للمقيمين',
                ],
                'marine' => [
                    'table'        => 'marine_structure_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين الهياكل البحرية',
                ],
                'medical' => [
                    'table'        => 'professional_liability_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين المسؤولية المهنية (الطبية)',
                ],
                'personal_accident' => [
                    'table'        => 'personal_accident_insurance_documents',
                    'date_col'     => 'issue_date',
                    'number_field' => 'insurance_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين الحوادث الشخصية',
                ],
                'school_student' => [
                    'table'        => 'school_student_insurance_documents',
                    'date_col'     => 'start_date',
                    'number_field' => 'policy_number',
                    'name_field'   => 'student_name',
                    'type_label'   => 'تأمين حماية طلاب المدارس',
                ],
                'cash_in_transit' => [
                    'table'        => 'cash_in_transit_insurance_documents',
                    'date_col'     => 'start_date',
                    'number_field' => 'policy_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين نقل النقدية',
                ],
                'cargo' => [
                    'table'        => 'cargo_insurance_documents',
                    'date_col'     => 'created_at',
                    'number_field' => 'policy_number',
                    'name_field'   => 'insured_name',
                    'type_label'   => 'تأمين شحن البضائع',
                ],
            ];

            $schema = DB::getSchemaBuilder();
            $documentsList = [];
            $totalSales = 0.0;
            $totalAgentShare = 0.0;
            $totalCompanyShare = 0.0;
            $activeCount = 0;
            $expiredCount = 0;
            $canceledCount = 0;
            $todayStr = \Carbon\Carbon::today()->format('Y-m-d');

            foreach ($documentTables as $typeKey => $config) {
                if ($docTypeFilter && $docTypeFilter !== 'all' && $docTypeFilter !== $typeKey) {
                    continue;
                }

                $tableName   = $config['table'];
                $numberField = $config['number_field'];
                $nameField   = $config['name_field'];
                $defaultLabel= $config['type_label'];

                if (!$schema->hasTable($tableName)) continue;
                if (!$schema->hasColumn($tableName, 'branch_agent_id')) continue;

                // تحديد عمود التاريخ بالأولوية: issue_date > start_date > created_at
                $hasIssueDate = $schema->hasColumn($tableName, 'issue_date');
                $hasStartDate = $schema->hasColumn($tableName, 'start_date');
                $filterDateCol = $hasIssueDate ? 'issue_date' : ($hasStartDate ? 'start_date' : 'created_at');

                $query = DB::table($tableName)->where('branch_agent_id', $agentId);

                // Date filtering using preferred date column
                $query->whereBetween($filterDateCol, [$fromDate, $toDate]);

                if ($search) {
                    $query->where(function ($q) use ($numberField, $nameField, $search, $tableName, $schema) {
                        $q->where($numberField, 'like', "%{$search}%");
                        if ($schema->hasColumn($tableName, $nameField)) {
                            $q->orWhere($nameField, 'like', "%{$search}%");
                        }
                        if ($schema->hasColumn($tableName, 'chassis_number')) {
                            $q->orWhere('chassis_number', 'like', "%{$search}%");
                        }
                    });
                }

                $docs = $query->orderBy('id', 'desc')->get();

                foreach ($docs as $doc) {
                    $docNum   = $doc->$numberField ?? ($doc->insurance_number ?? $doc->document_number ?? $doc->policy_number ?? '-');
                    $name     = $doc->$nameField ?? ($doc->insured_name ?? $doc->name ?? $doc->student_name ?? '-');
                    $total    = (float)($doc->total ?? $doc->premium_amount ?? 0);
                    $premium  = (float)($doc->premium ?? $doc->premium_amount ?? 0);
                    $rawDate  = $doc->issue_date ?? $doc->start_date ?? $doc->created_at ?? $fromDate;
                    $typeLabel= $doc->insurance_type ?? $defaultLabel;

                    $pct           = \App\Helpers\AgentPercentageHelper::resolvePercentage($percentages, $typeLabel, $rawDate);
                    $agentAmount   = round($premium * ($pct / 100), 2);
                    $companyAmount = round($total - $agentAmount, 2);

                    // Check cancellation & expiration status
                    $isCanceled = false;
                    if ((isset($doc->is_canceled) && $doc->is_canceled) || (isset($doc->canceled_at) && $doc->canceled_at !== null) || (isset($doc->status) && in_array(mb_strtolower(trim($doc->status)), ['ملغية', 'ملغيه', 'canceled', 'cancelled']))) {
                        $isCanceled = true;
                    }

                    $isExpired = false;
                    if (!$isCanceled) {
                        if (!empty($doc->end_date) && \Carbon\Carbon::parse($doc->end_date)->format('Y-m-d') < $todayStr) {
                            $isExpired = true;
                        } elseif (isset($doc->status) && in_array(mb_strtolower(trim($doc->status)), ['منتهية', 'منتهيه', 'expired'])) {
                            $isExpired = true;
                        }
                    }

                    if ($isCanceled) {
                        $statusStr = 'ملغية';
                        $canceledCount++;
                    } elseif ($isExpired) {
                        $statusStr = 'منتهية';
                        $expiredCount++;
                    } else {
                        $statusStr = 'نشطة';
                        $activeCount++;
                    }

                    // Canceled documents DO NOT add to revenue or company/agent shares!
                    if (!$isCanceled) {
                        $totalSales        += $total;
                        $totalAgentShare   += $agentAmount;
                        $totalCompanyShare += $companyAmount;
                    }

                    $documentsList[] = [
                        'id'              => $doc->id,
                        'table'           => $tableName,
                        'document_type'   => $typeKey,
                        'type_label'      => $typeLabel,
                        'document_number' => $docNum,
                        'insured_name'    => $name,
                        'issue_date'      => $doc->issue_date ?? ($doc->start_date ?? ($doc->created_at ?? '-')),
                        'start_date'      => $doc->start_date ?? ($doc->issue_date ?? '-'),
                        'end_date'        => $doc->end_date ?? '-',
                        'premium'         => $premium,
                        'total'           => $total,
                        'percentage'      => $pct,
                        'agent_share'     => $agentAmount,
                        'company_share'   => $companyAmount,
                        'is_old_document' => (bool)($doc->is_old_document ?? false),
                        'status'          => $statusStr,
                        'notes'           => $doc->notes ?? null,
                    ];
                }
            }

            // Sort documents by date desc
            usort($documentsList, function ($a, $b) {
                return strcmp((string)$b['issue_date'], (string)$a['issue_date']);
            });

            return response()->json([
                'success'   => true,
                'documents' => $documentsList,
                'summary'   => [
                    'total_documents'     => count($documentsList),
                    'active_documents'    => $activeCount,
                    'expired_documents'   => $expiredCount,
                    'canceled_documents'  => $canceledCount,
                    'total_sales'         => round($totalSales, 2),
                    'total_agent_share'   => round($totalAgentShare, 2),
                    'total_company_share' => round($totalCompanyShare, 2),
                ],
            ]);

            return response()->json([
                'success'   => true,
                'documents' => $documentsList,
                'summary'   => [
                    'total_documents'     => count($documentsList),
                    'total_sales'         => round($totalSales, 2),
                    'total_agent_share'   => round($totalAgentShare, 2),
                    'total_company_share' => round($totalCompanyShare, 2),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب وثائق الشهر',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Update details of a specific document directly from month documents view.
     */
    public function updateAgentMonthDocument(Request $request)
    {
        try {
            $tableName  = $request->input('table');
            $documentId = $request->input('id');

            if (!$tableName || !$documentId) {
                return response()->json(['success' => false, 'message' => 'يرجى تحديد جدول والـ ID الخاص بالوثيقة'], 422);
            }

            $schema = DB::getSchemaBuilder();
            if (!$schema->hasTable($tableName)) {
                return response()->json(['success' => false, 'message' => 'جدول الوثيقة غير موجود'], 404);
            }

            $doc = DB::table($tableName)->where('id', $documentId)->first();
            if (!$doc) {
                return response()->json(['success' => false, 'message' => 'الوثيقة غير موجودة'], 404);
            }

            $updateData = [];

            if ($request->has('insured_name')) {
                if ($schema->hasColumn($tableName, 'insured_name')) {
                    $updateData['insured_name'] = $request->input('insured_name');
                } elseif ($schema->hasColumn($tableName, 'name')) {
                    $updateData['name'] = $request->input('insured_name');
                } elseif ($schema->hasColumn($tableName, 'student_name')) {
                    $updateData['student_name'] = $request->input('insured_name');
                }
            }

            if ($request->has('document_number')) {
                if ($schema->hasColumn($tableName, 'insurance_number')) {
                    $updateData['insurance_number'] = $request->input('document_number');
                } elseif ($schema->hasColumn($tableName, 'document_number')) {
                    $updateData['document_number'] = $request->input('document_number');
                } elseif ($schema->hasColumn($tableName, 'policy_number')) {
                    $updateData['policy_number'] = $request->input('document_number');
                }
            }

            if ($request->has('total') && $schema->hasColumn($tableName, 'total')) {
                $updateData['total'] = (float)$request->input('total');
            }

            if ($request->has('premium') && $schema->hasColumn($tableName, 'premium')) {
                $updateData['premium'] = (float)$request->input('premium');
            }

            if ($request->has('start_date') && $schema->hasColumn($tableName, 'start_date')) {
                $updateData['start_date'] = $request->input('start_date');
            }

            if ($request->has('end_date') && $schema->hasColumn($tableName, 'end_date')) {
                $updateData['end_date'] = $request->input('end_date');
            }

            if ($request->has('issue_date') && $schema->hasColumn($tableName, 'issue_date')) {
                $updateData['issue_date'] = $request->input('issue_date');
            }

            if ($request->has('notes') && $schema->hasColumn($tableName, 'notes')) {
                $updateData['notes'] = $request->input('notes');
            }

            if ($request->has('status') && $schema->hasColumn($tableName, 'status')) {
                $updateData['status'] = $request->input('status');
            }

            if (!empty($updateData)) {
                if ($schema->hasColumn($tableName, 'updated_at')) {
                    $updateData['updated_at'] = now();
                }
                DB::table($tableName)->where('id', $documentId)->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الوثيقة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث الوثيقة',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Delete a specific document directly from month documents view.
     */
    public function deleteAgentMonthDocument(Request $request)
    {
        try {
            $tableName  = $request->input('table');
            $documentId = $request->input('id');

            if (!$tableName || !$documentId) {
                return response()->json(['success' => false, 'message' => 'يرجى تحديد جدول والـ ID الخاص بالوثيقة'], 422);
            }

            $schema = DB::getSchemaBuilder();
            if (!$schema->hasTable($tableName)) {
                return response()->json(['success' => false, 'message' => 'جدول الوثيقة غير موجود'], 404);
            }

            $deleted = DB::table($tableName)->where('id', $documentId)->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'الوثيقة غير موجودة أو تم حذفها مسبقاً'], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الوثيقة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الوثيقة',
                'error'   => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Get Comprehensive Production Portfolio data for JSON and Excel exports.
     */
    public function getComprehensiveProductionPortfolio(Request $request)
    {
        try {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(300);

            $data = $this->buildComprehensiveProductionData($request);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعداد تقرير الحوافظ الشامل: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Print Comprehensive Production Portfolio (A4 Landscape template).
     */
    public function printComprehensiveProductionPortfolio(Request $request)
    {
        try {
            @ini_set('memory_limit', '1024M');
            @set_time_limit(300);

            $data = $this->buildComprehensiveProductionData($request);

            if ($request->get('print_mode') === 'summary') {
                return response($this->renderExecutiveSummaryPrintHtml($data));
            }

            if (($data['grand_totals']['documents_count'] ?? 0) > 400 && !$request->has('force_all') && !$request->has('chunk')) {
                return response($this->renderLargeDetailedPrintHtml($data, $request));
            }

            return response($this->renderFastDetailedPrintHtml($data, $request));
        } catch (\Exception $e) {
            abort(404, 'حدث خطأ أثناء إعداد تقرير الحوافظ للطباعة: ' . $e->getMessage());
        }
    }

    /**
     * Internal helper to build comprehensive production data.
     */
    private function buildComprehensiveProductionData(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $agentId = $request->get('agent_id');
        $year = $request->get('year');
        $month = $request->get('month');
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $documentTypeFilter = $request->get('document_type', 'all');
        $excludeCanceled = $request->boolean('exclude_canceled', false);
        $printMode = $request->get('print_mode', 'detailed'); // 'summary' or 'detailed'

        $schema = DB::getSchemaBuilder();
        $usersMap = [];
        if ($schema->hasTable('users')) {
            $usersMap = DB::table('users')->pluck('name', 'id')->toArray();
        }
        
        $branchAgents = collect();
        if ($schema->hasTable('branches_agents')) {
            $branchAgents = DB::table('branches_agents')->get(['id', 'agency_name', 'agent_name', 'code'])->keyBy('id');
        }

        $selectedAgent = null;
        if ($agentId && $agentId !== 'all' && is_numeric($agentId)) {
            $selectedAgent = $branchAgents->get((int)$agentId);
        }

        $tablesConfig = [
            'compulsory' => [
                'table' => 'insurance_documents',
                'title' => 'تأمين إجباري سيارات',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'plate_number_manual',
                'detail_field' => 'engine_power',
                'detail_header' => 'قوة المحرك بالحصان',
                'date_field' => 'issue_date',
            ],
            'international' => [
                'table' => 'international_insurance_documents',
                'title' => 'تأمين السيارات الدولي (البطاقة البرتقالية)',
                'number_field' => 'document_number',
                'name_field' => 'insured_name',
                'plate_field' => 'plate_number',
                'detail_field' => 'item_type',
                'detail_header' => 'نوع المركبة / البند',
                'date_field' => 'issue_date',
            ],
            'travel' => [
                'table' => 'travel_insurance_documents',
                'title' => 'تأمين المسافرين',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'geographic_area',
                'detail_field' => 'duration',
                'detail_header' => 'مدة التأمين / الوجهة',
                'date_field' => 'issue_date',
            ],
            'resident' => [
                'table' => 'resident_insurance_documents',
                'title' => 'تأمين الوافدين للمقيمين',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'residence_type',
                'detail_field' => 'occupation',
                'detail_header' => 'المهنة / صفة الإقامة',
                'date_field' => 'issue_date',
            ],
            'marine' => [
                'table' => 'marine_structure_insurance_documents',
                'title' => 'تأمين الهياكل البحرية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'structure_name',
                'detail_field' => 'structure_type',
                'detail_header' => 'نوع الهيكل البحري',
                'date_field' => 'issue_date',
            ],
            'medical' => [
                'table' => 'professional_liability_insurance_documents',
                'title' => 'تأمين المسؤولية المهنية (الطبية)',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'workplace',
                'detail_field' => 'profession',
                'detail_header' => 'المهنة / جهة العمل',
                'date_field' => 'issue_date',
            ],
            'personal_accident' => [
                'table' => 'personal_accident_insurance_documents',
                'title' => 'تأمين الحوادث الشخصية',
                'number_field' => 'insurance_number',
                'name_field' => 'insured_name',
                'plate_field' => 'job',
                'detail_field' => 'coverage_type',
                'detail_header' => 'نوع التغطية والمهنة',
                'date_field' => 'issue_date',
            ],
            'school_student' => [
                'table' => 'school_student_insurance_documents',
                'title' => 'تأمين حماية طلاب المدارس',
                'number_field' => 'policy_number',
                'name_field' => 'insured_name',
                'plate_field' => 'school_name',
                'detail_field' => 'student_count',
                'detail_header' => 'المؤسسة التعليمية',
                'date_field' => 'start_date',
            ],
            'cash_in_transit' => [
                'table' => 'cash_in_transit_insurance_documents',
                'title' => 'تأمين نقل النقدية',
                'number_field' => 'policy_number',
                'name_field' => 'insured_name',
                'plate_field' => 'transit_route',
                'detail_field' => 'limit_per_transit',
                'detail_header' => 'خط السير وحد النقل',
                'date_field' => 'start_date',
            ],
            'cargo' => [
                'table' => 'cargo_insurance_documents',
                'title' => 'تأمين شحن ونقل البضائع',
                'number_field' => 'policy_number',
                'name_field' => 'insured_name',
                'plate_field' => 'bill_of_lading',
                'detail_field' => 'cargo_type',
                'detail_header' => 'نوع البضاعة والبيان',
                'date_field' => 'created_at',
            ],
        ];

        $sections = [];
        $grandTotals = [
            'documents_count' => 0,
            'premium' => 0.0,
            'tax' => 0.0,
            'supervision_fees' => 0.0,
            'stamp' => 0.0,
            'issue_fees' => 0.0,
            'total' => 0.0,
        ];

        foreach ($tablesConfig as $typeKey => $cfg) {
            if ($documentTypeFilter && $documentTypeFilter !== 'all' && $documentTypeFilter !== $typeKey && $documentTypeFilter !== $cfg['table']) {
                continue;
            }

            $tableName = $cfg['table'];
            if (!$schema->hasTable($tableName)) continue;

            $hasBranchAgentCol = $schema->hasColumn($tableName, 'branch_agent_id');
            $hasStatusCol = $schema->hasColumn($tableName, 'status');
            $hasIssueDateCol = $schema->hasColumn($tableName, 'issue_date');
            $hasStartDateCol = $schema->hasColumn($tableName, 'start_date');
            $dateCol = $hasIssueDateCol ? 'issue_date' : ($hasStartDateCol ? 'start_date' : 'created_at');

            $hasPlateCol = $schema->hasColumn($tableName, $cfg['plate_field']);
            $hasDetailCol = $schema->hasColumn($tableName, $cfg['detail_field']);
            $hasPlateNumber = $schema->hasColumn($tableName, 'plate_number');
            $hasChassisNumber = $schema->hasColumn($tableName, 'chassis_number');

            $query = DB::table($tableName);

            // Agent filter
            if ($selectedAgent && $hasBranchAgentCol) {
                $query->where('branch_agent_id', $selectedAgent->id);
            }

            // High-performance date range filtering using DB indexes
            if ($fromDate && $toDate) {
                $query->whereBetween($dateCol, [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
            } elseif ($year && $month) {
                $m = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
                $startDate = "{$year}-{$m}-01 00:00:00";
                $daysInMonth = date('t', strtotime("{$year}-{$m}-01"));
                $endDate = "{$year}-{$m}-{$daysInMonth} 23:59:59";
                $query->whereBetween($dateCol, [$startDate, $endDate]);
            } elseif ($year) {
                $query->whereBetween($dateCol, ["{$year}-01-01 00:00:00", "{$year}-12-31 23:59:59"]);
            }

            if ($excludeCanceled && $hasStatusCol) {
                $query->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'ملغية');
                });
            }

            $hasTax = $schema->hasColumn($tableName, 'tax');
            $hasSupervision = $schema->hasColumn($tableName, 'supervision_fees');
            $hasStamp = $schema->hasColumn($tableName, 'stamp');
            $hasIssueFees = $schema->hasColumn($tableName, 'issue_fees');
            $premCol = $schema->hasColumn($tableName, 'premium') ? 'premium' : ($schema->hasColumn($tableName, 'premium_amount') ? 'premium_amount' : null);
            $hasTotal = $schema->hasColumn($tableName, 'total');

            $premExpr = $premCol ? "COALESCE(SUM({$premCol}), 0)" : "0";
            $taxExpr = $hasTax ? "COALESCE(SUM(tax), 0)" : "0";
            $supExpr = $hasSupervision ? "COALESCE(SUM(supervision_fees), 0)" : "0";
            $stampExpr = $hasStamp ? "COALESCE(SUM(stamp), 0)" : "0";
            $issExpr = $hasIssueFees ? "COALESCE(SUM(issue_fees), 0)" : "0";
            $totExpr = $hasTotal ? "COALESCE(SUM(total), 0)" : "0";

            // Quick Aggregation with SQL (Takes 2ms)
            $stats = (clone $query)->selectRaw("
                COUNT(*) as cnt,
                {$premExpr} as total_premium,
                {$taxExpr} as total_tax,
                {$supExpr} as total_supervision_fees,
                {$stampExpr} as total_stamp,
                {$issExpr} as total_issue_fees,
                {$totExpr} as total_total
            ")->first();

            $docCount = (int)($stats->cnt ?? 0);
            if ($docCount === 0) {
                continue;
            }

            $premSum = (float)($stats->total_premium ?? 0);
            $taxSum = (float)($stats->total_tax ?? 0);
            $supSum = (float)($stats->total_supervision_fees ?? 0);
            $stampSum = (float)($stats->total_stamp ?? 0);
            $issSum = (float)($stats->total_issue_fees ?? 0);
            $totSum = (float)($stats->total_total ?? 0);

            // If total column was 0 or not calculated in DB, calculate it
            if ($totSum == 0 && ($premSum > 0 || $taxSum > 0 || $supSum > 0)) {
                $totSum = $premSum + $taxSum + $supSum + $stampSum + $issSum;
            }

            $sectionTotals = [
                'documents_count' => $docCount,
                'premium' => $premSum,
                'tax' => $taxSum,
                'supervision_fees' => $supSum,
                'stamp' => $stampSum,
                'issue_fees' => $issSum,
                'total' => $totSum,
            ];

            $grandTotals['documents_count'] += $docCount;
            $grandTotals['premium'] += $premSum;
            $grandTotals['tax'] += $taxSum;
            $grandTotals['supervision_fees'] += $supSum;
            $grandTotals['stamp'] += $stampSum;
            $grandTotals['issue_fees'] += $issSum;
            $grandTotals['total'] += $totSum;

            $docRows = [];

            // Only fetch individual document records if not in summary-only print mode
            if ($printMode !== 'summary') {
                $numField = $cfg['number_field'];
                $nameField = $cfg['name_field'];
                $plateField = $cfg['plate_field'];
                $detField = $cfg['detail_field'];

                // Select only the columns needed to minimize DB IO and memory usage
                $selectCols = ['id'];
                if ($numField) $selectCols[] = $numField;
                if ($nameField && !in_array($nameField, $selectCols)) $selectCols[] = $nameField;
                if ($dateCol && !in_array($dateCol, $selectCols)) $selectCols[] = $dateCol;
                if ($hasPlateCol && !in_array($cfg['plate_field'], $selectCols)) $selectCols[] = $cfg['plate_field'];
                if ($hasPlateNumber && !in_array('plate_number', $selectCols)) $selectCols[] = 'plate_number';
                if ($hasChassisNumber && !in_array('chassis_number', $selectCols)) $selectCols[] = 'chassis_number';
                if ($hasDetailCol && !in_array($cfg['detail_field'], $selectCols)) $selectCols[] = $cfg['detail_field'];
                if ($premCol && !in_array($premCol, $selectCols)) $selectCols[] = $premCol;
                if ($hasTax) $selectCols[] = 'tax';
                if ($hasSupervision) $selectCols[] = 'supervision_fees';
                if ($hasStamp) $selectCols[] = 'stamp';
                if ($hasIssueFees) $selectCols[] = 'issue_fees';
                if ($hasTotal) $selectCols[] = 'total';
                if ($hasBranchAgentCol) $selectCols[] = 'branch_agent_id';
                if ($schema->hasColumn($tableName, 'user_id')) $selectCols[] = 'user_id';

                $docs = $query->select(array_values(array_unique($selectCols)))
                              ->orderBy($dateCol, 'desc')
                              ->get();

                foreach ($docs as $doc) {
                    $docNum = $doc->$numField ?? ($doc->insurance_number ?? ($doc->document_number ?? ($doc->policy_number ?? '-')));
                    $insuredName = $doc->$nameField ?? ($doc->insured_name ?? ($doc->name ?? ($doc->student_name ?? '-')));
                    
                    $plateNum = '-';
                    if ($hasPlateCol && isset($doc->$plateField)) {
                        $plateNum = $doc->$plateField;
                    } elseif ($hasPlateNumber && isset($doc->plate_number)) {
                        $plateNum = $doc->plate_number;
                    } elseif ($hasChassisNumber && isset($doc->chassis_number)) {
                        $plateNum = $doc->chassis_number;
                    }

                    $extraDetail = ($hasDetailCol && isset($doc->$detField)) ? $doc->$detField : '-';

                    $docDate = $doc->$dateCol ?? ($doc->issue_date ?? ($doc->start_date ?? ($doc->created_at ?? '-')));
                    if ($docDate && $docDate !== '-') {
                        $docDate = date('d/m/Y', strtotime($docDate));
                    }

                    $prem = $premCol ? (float)($doc->$premCol ?? 0) : 0;
                    $taxVal = $hasTax ? (float)($doc->tax ?? 0) : 0;
                    $supVal = $hasSupervision ? (float)($doc->supervision_fees ?? 0) : 0;
                    $stmpVal = $hasStamp ? (float)($doc->stamp ?? 0) : 0;
                    $issVal = $hasIssueFees ? (float)($doc->issue_fees ?? 0) : 0;
                    $totVal = $hasTotal ? (float)($doc->total ?? 0) : ($prem + $taxVal + $supVal + $stmpVal + $issVal);

                    if ($totVal == 0 && ($prem > 0 || $taxVal > 0 || $supVal > 0)) {
                        $totVal = $prem + $taxVal + $supVal + $stmpVal + $issVal;
                    }

                    $agentObj = (isset($doc->branch_agent_id) && isset($branchAgents[$doc->branch_agent_id])) ? $branchAgents[$doc->branch_agent_id] : null;
                    $agencyName = $agentObj ? ($agentObj->agency_name ?? $agentObj->agent_name) : '-';
                    
                    $userId = $doc->user_id ?? null;
                    $userName = ($userId && isset($usersMap[$userId])) ? $usersMap[$userId] : $agencyName;

                    $docRows[] = [
                        'id' => $doc->id,
                        'document_number' => $docNum,
                        'insured_name' => $insuredName,
                        'issue_date' => $docDate,
                        'plate_number' => $plateNum,
                        'premium' => $prem,
                        'tax' => $taxVal,
                        'supervision_fees' => $supVal,
                        'stamp' => $stmpVal,
                        'issue_fees' => $issVal,
                        'extra_detail' => $extraDetail,
                        'total' => $totVal,
                        'agency_name' => $agencyName,
                        'user_name' => $userName,
                    ];
                }
            }

            $sections[] = [
                'key' => $typeKey,
                'title' => $cfg['title'],
                'detail_header' => $cfg['detail_header'],
                'documents' => $docRows,
                'totals' => $sectionTotals,
            ];
        }

        // Period Label
        $periodLabel = 'كافة الفترات المسجلة';
        if ($fromDate && $toDate) {
            $periodLabel = "من تاريخ {$fromDate} إلى تاريخ {$toDate}";
        } elseif ($year && $month) {
            $periodLabel = "شهر {$month} لعام {$year}";
        } elseif ($year) {
            $periodLabel = "خلال عام {$year}";
        }

        // Agent Label
        $agentLabel = $selectedAgent ? ($selectedAgent->agency_name . ' (' . ($selectedAgent->code ?? '') . ')') : 'جميع الوكلاء والفروع (الكل)';

        return [
            'sections' => $sections,
            'grand_totals' => $grandTotals,
            'selected_agent' => $selectedAgent,
            'agent_label' => $agentLabel,
            'period_label' => $periodLabel,
            'year' => $year,
            'month' => $month,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'document_type' => $documentTypeFilter,
            'print_mode' => $printMode,
        ];
    }

    /**
     * Render the official Executive Financial Summary print sheet (A4 Landscape, 1-2 pages, zero lag).
     */
    private function renderExecutiveSummaryPrintHtml(array $data): string
    {
        $agentLabel = htmlspecialchars($data['agent_label'] ?? 'الكل', ENT_QUOTES, 'UTF-8');
        $periodLabel = htmlspecialchars($data['period_label'] ?? '', ENT_QUOTES, 'UTF-8');
        $grandTotals = $data['grand_totals'] ?? [];
        $sections = $data['sections'] ?? [];
        $grandTotalVal = max(0.001, (float)($grandTotals['total'] ?? 0));
        $dateStr = date('d/m/Y h:i A');

        $rowsHtml = '';
        $idx = 1;
        foreach ($sections as $sec) {
            $title = htmlspecialchars($sec['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $cnt = number_format($sec['totals']['documents_count'] ?? 0);
            $prem = number_format($sec['totals']['premium'] ?? 0, 3);
            $tax = number_format($sec['totals']['tax'] ?? 0, 3);
            $sup = number_format($sec['totals']['supervision_fees'] ?? 0, 3);
            $stampAndFees = number_format(($sec['totals']['stamp'] ?? 0) + ($sec['totals']['issue_fees'] ?? 0), 3);
            $tot = number_format($sec['totals']['total'] ?? 0, 3);
            $pct = number_format((($sec['totals']['total'] ?? 0) / $grandTotalVal) * 100, 1) . '%';

            $rowsHtml .= "
                <tr>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;text-align:center;\">{$idx}</td>
                    <td style=\"padding:7px 10px;border:1px solid #cbd5e1;font-weight:800;color:#0284c7;text-align:right;\">{$title}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;font-weight:800;text-align:center;\">{$cnt}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;text-align:center;\">{$prem}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;text-align:center;\">{$tax}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;text-align:center;\">{$sup}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;text-align:center;\">{$stampAndFees}</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;font-weight:900;color:#15803d;text-align:center;\">{$tot} د.ل</td>
                    <td style=\"padding:7px;border:1px solid #cbd5e1;color:#475569;text-align:center;\">{$pct}</td>
                </tr>
            ";
            $idx++;
        }

        $cntTotal = number_format($grandTotals['documents_count'] ?? 0);
        $premTotal = number_format($grandTotals['premium'] ?? 0, 3);
        $taxTotal = number_format($grandTotals['tax'] ?? 0, 3);
        $supTotal = number_format($grandTotals['supervision_fees'] ?? 0, 3);
        $stampTotal = number_format(($grandTotals['stamp'] ?? 0) + ($grandTotals['issue_fees'] ?? 0), 3);
        $grandTot = number_format($grandTotals['total'] ?? 0, 3);

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف الملخص المالي المعتمد للحوافظ - شركة المدار الليبي للتأمين</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 landscape; margin: 8mm 10mm; }
        * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        body { font-family: 'Tajawal', Tahoma, sans-serif; font-size: 11.5px; color: #0f172a; background: #fff; padding: 10px; }
        .hdr { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #0284c7; padding-bottom: 8px; margin-bottom: 12px; }
        .hdr h1 { font-size: 19px; color: #0284c7; font-weight: 900; }
        .badge { background: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; padding: 4px 18px; border-radius: 6px; font-weight: 800; font-size: 13px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; margin-bottom: 14px; overflow: hidden; }
        .info-cell { padding: 8px 12px; text-align: center; border-left: 1px solid #cbd5e1; }
        .info-cell:last-child { border-left: none; }
        .info-lbl { font-size: 11px; color: #475569; font-weight: 700; margin-bottom: 2px; }
        .info-val { font-size: 13px; font-weight: 800; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 11px; }
        th { background: #0284c7; color: #fff; font-weight: 800; padding: 8px 6px; border: 1px solid #0369a1; text-align: center; }
        .grand-row td { background: #e0f2fe; font-weight: 900; font-size: 12px; color: #0369a1; border-top: 2px solid #0284c7; }
        .declaration { background: #f8fafc; border: 1px dashed #94a3b8; border-radius: 6px; padding: 10px 14px; font-size: 11px; margin-bottom: 15px; line-height: 1.6; }
        .sig-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 15px; }
        .sig-box { border: 1.5px solid #64748b; border-radius: 6px; overflow: hidden; background: #fff; text-align: center; }
        .sig-title { background: #f1f5f9; padding: 6px; font-weight: 800; font-size: 11px; border-bottom: 1px solid #64748b; }
        .sig-space { height: 50px; }
        .ftr { display: flex; justify-content: space-between; border-top: 1px solid #94a3b8; padding-top: 6px; font-size: 10px; color: #64748b; font-weight: 700; }
        .no-print { display: flex; justify-content: space-between; align-items: center; background: #f0fdf4; border: 1.5px solid #10b981; padding: 10px 16px; border-radius: 8px; margin-bottom: 15px; }
        .btn { padding: 6px 16px; border-radius: 6px; font-weight: 800; cursor: pointer; border: none; font-family: inherit; font-size: 12px; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print">
        <span style="color:#047857;font-weight:800;">📄 كشف الملخص المالي المعتمد للحوافظ والإنتاجية (مجهز للطباعة بمقاس A4 أفقي)</span>
        <div>
            <button onclick="window.print()" class="btn" style="background:#10b981;color:#fff;">🖨️ طباعة المستند الآن</button>
            <button onclick="window.close()" class="btn" style="background:#e2e8f0;color:#1e293b;margin-right:6px;">إغلاق</button>
        </div>
    </div>

    <div class="hdr">
        <div style="font-weight:900;color:#139625;font-size:12px;">المدار الليبي<br><span style="color:#0284c7;">للتأمين</span></div>
        <div style="text-align:center;">
            <h1>شركة المدار الليبي للتأمين</h1>
            <div class="badge">كشف الملخص المالي المعتمد لحوافظ الإنتاجية</div>
        </div>
        <div style="width:50px;"></div>
    </div>

    <div class="info-grid">
        <div class="info-cell">
            <div class="info-lbl">نطاق الوكلاء والفروع</div>
            <div class="info-val">{$agentLabel}</div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">الفترة المحددة</div>
            <div class="info-val" style="color:#0284c7;">{$periodLabel}</div>
        </div>
        <div class="info-cell">
            <div class="info-lbl">إجمالي عدد الوثائق</div>
            <div class="info-val">{$cntTotal} وثيقة</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:35px;">#</th>
                <th style="text-align:right;padding-right:10px;">نوع التأمين</th>
                <th style="width:80px;">عدد الوثائق</th>
                <th style="width:110px;">القسط الصافي (د.ل)</th>
                <th style="width:90px;">الضرائب (د.ل)</th>
                <th style="width:95px;">أ. ورقابة (د.ل)</th>
                <th style="width:110px;">الدمغة وم. الإصدار</th>
                <th style="width:120px;">المجموع الإجمالي (د.ل)</th>
                <th style="width:70px;">النسبة %</th>
            </tr>
        </thead>
        <tbody>
            {$rowsHtml}
            <tr class="grand-row">
                <td colspan="2" style="text-align:center;padding:8px;border:1px solid #0284c7;">المجموع العام الإجمالي لكافة التأمينات</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">{$cntTotal}</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">{$premTotal}</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">{$taxTotal}</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">{$supTotal}</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">{$stampTotal}</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;color:#0f172a;font-size:13px;">{$grandTot} د.ل</td>
                <td style="text-align:center;padding:8px;border:1px solid #0284c7;">100%</td>
            </tr>
        </tbody>
    </table>

    <div class="declaration">
        <strong>إقرار ومصادقة:</strong>
        نقر ونشهد بصحة واكتمال كافة العمليات والبيانات المالية والرسوم والضرائب المدرجة أعلاه والمستخرجة من منظومة شركة المدار الليبي للتأمين للفترة المحددة، وقد تمت المطابقة المحاسبية والدفترية وفق القوانين واللوائح السارية.
    </div>

    <div class="sig-grid">
        <div class="sig-box">
            <div class="sig-title">إعداد رئيس قسم الإصدار / الفروع</div>
            <div class="sig-space"></div>
        </div>
        <div class="sig-box">
            <div class="sig-title">التدقيق والمراجعة المالية</div>
            <div class="sig-space"></div>
        </div>
        <div class="sig-box">
            <div class="sig-title">اعتماد المدير المالي</div>
            <div class="sig-space"></div>
        </div>
        <div class="sig-box">
            <div class="sig-title">الختم الرسمي للشركة</div>
            <div class="sig-space"></div>
        </div>
    </div>

    <div class="ftr">
        <div>منظومة شركة المدار الليبي للتأمين - تقرير الحوافظ الشامل</div>
        <div>تاريخ الاستخراج: {$dateStr}</div>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
HTML;
    }

    /**
     * Render safety print interface when documents count is too large for browser layout.
     */
    private function renderLargeDetailedPrintHtml(array $data, Request $request): string
    {
        $totalDocs = number_format($data['grand_totals']['documents_count'] ?? 0);
        $summaryUrl = $request->fullUrlWithQuery(['print_mode' => 'summary']);
        $forceAllUrl = $request->fullUrlWithQuery(['force_all' => '1']);
        $grandTot = number_format($data['grand_totals']['total'] ?? 0, 3);
        $agentLabel = htmlspecialchars($data['agent_label'] ?? '', ENT_QUOTES, 'UTF-8');
        $periodLabel = htmlspecialchars($data['period_label'] ?? '', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تنبيه حجم الطباعة - شركة المدار الليبي للتأمين</title>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', Tahoma, sans-serif; background: #f8fafc; color: #0f172a; padding: 40px 20px; direction: rtl; }
        .card { max-width: 750px; margin: 0 auto; background: #fff; border-radius: 16px; border: 1.5px solid #e2e8f0; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); text-align: center; }
        .icon { font-size: 48px; color: #d97706; margin-bottom: 16px; }
        h2 { font-size: 22px; color: #1e293b; margin-bottom: 12px; }
        p { font-size: 14px; color: #475569; line-height: 1.7; margin-bottom: 24px; }
        .kpi-box { background: #f1f5f9; border-radius: 12px; padding: 16px; margin-bottom: 28px; display: flex; justify-content: space-around; }
        .kpi-item { display: flex; flex-direction: column; }
        .kpi-lbl { font-size: 12px; color: #64748b; font-weight: 700; }
        .kpi-val { font-size: 18px; font-weight: 900; color: #0284c7; }
        .actions { display: flex; flex-direction: column; gap: 12px; }
        .btn { padding: 14px 24px; border-radius: 10px; font-weight: 800; font-size: 14px; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; font-family: inherit; transition: all 0.2s; }
        .btn-summary { background: #10b981; color: #fff; box-shadow: 0 4px 14px rgba(16,185,129,0.3); }
        .btn-summary:hover { background: #059669; }
        .btn-force { background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1; }
        .btn-force:hover { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">⚠️</div>
        <h2>كشف كبير الحجم ({$totalDocs} وثيقة)</h2>
        <p>
            يحتوي التقرير المحدد على <strong>{$totalDocs} وثيقة</strong> بإجمالي مالي <strong>{$grandTot} د.ل</strong>.<br>
            طباعة هذا العدد الضخم من الوثائق الفردية يستهلك مئات الصفحات ويؤدي إلى بطء أو تجمّد المتصفح.<br>
            <strong>يُوصى باعتماد وطباعة "الملخص المالي المعتمد" (صفحة واحدة)، أو تصدير الوثائق إلى Excel عبر الشاشة الرئيسية.</strong>
        </p>

        <div class="kpi-box">
            <div class="kpi-item">
                <span class="kpi-lbl">النطاق</span>
                <span class="kpi-val" style="font-size:14px;">{$agentLabel}</span>
            </div>
            <div class="kpi-item">
                <span class="kpi-lbl">الفترة</span>
                <span class="kpi-val" style="font-size:14px;">{$periodLabel}</span>
            </div>
            <div class="kpi-item">
                <span class="kpi-lbl">عدد الوثائق</span>
                <span class="kpi-val">{$totalDocs}</span>
            </div>
            <div class="kpi-item">
                <span class="kpi-lbl">المجموع الكلي</span>
                <span class="kpi-val">{$grandTot} د.ل</span>
            </div>
        </div>

        <div class="actions">
            <a href="{$summaryUrl}" class="btn btn-summary">
                📑 طباعة كشف الملخص المالي المعتمد (موصى به - صفحة واحدة جاهزة للاعتماد)
            </a>
            <a href="{$request->fullUrlWithQuery(['chunk' => '1', 'chunk_size' => '2000', 'force_all' => '1'])}" class="btn" style="background:#0284c7;color:#fff;">
                ⚡ طباعة سريعة بالحزم (جزء 1: من 1 إلى 2,000 وثيقة - توليد فوري في ثانيتين)
            </a>
            <a href="{$forceAllUrl}" class="btn btn-force">
                🖨️ طباعة كشف كافة الوثائق التفصيلي ({$totalDocs} وثيقة بالتنسيق فائق السرعة)
            </a>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * High-speed, pre-paginated detailed print renderer for large datasets (e.g. 8,500+ records).
     * Splits records into explicit per-page containers (.p-page) of fixed 198mm printable height.
     * Uses table-layout: fixed, compact 15.5px rows, separated borders, and 0 inline styles.
     * Guarantees instantaneous Chrome print preview without hanging on "Loading preview...".
     */
    private function renderFastDetailedPrintHtml(array $data, Request $request): string
    {
        $agentLabel = htmlspecialchars($data['agent_label'] ?? 'الكل', ENT_QUOTES, 'UTF-8');
        $periodLabel = htmlspecialchars($data['period_label'] ?? '', ENT_QUOTES, 'UTF-8');
        $grandTotals = $data['grand_totals'] ?? [];
        $sections = $data['sections'] ?? [];
        $totalDocsCount = (int)($grandTotals['documents_count'] ?? 0);
        $dateStr = date('d/m/Y h:i A');

        // Chunking support for ultra-fast preview
        $chunk = $request->get('chunk');
        $chunkSize = max(500, (int)$request->get('chunk_size', 2000));
        $isChunked = ($chunk && is_numeric($chunk));
        $chunkPage = $isChunked ? (int)$chunk : 1;
        $globalOffset = $isChunked ? ($chunkPage - 1) * $chunkSize : 0;
        $maxItemsToRender = $isChunked ? $chunkSize : PHP_INT_MAX;

        $logoPath = public_path('img/logo3.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        } elseif (file_exists(public_path('img/logo.png'))) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('img/logo.png')));
        }

        // 1. Filter / Slice documents across sections based on chunk parameters
        $globalItemCounter = 0;
        $filteredSections = [];
        $totalRenderedDocs = 0;

        foreach ($sections as $sec) {
            $secDocs = $sec['documents'] ?? [];
            $matchingDocs = [];

            foreach ($secDocs as $doc) {
                $globalItemCounter++;
                if ($isChunked) {
                    if ($globalItemCounter <= $globalOffset) continue;
                    if ($totalRenderedDocs >= $maxItemsToRender) break 2;
                }
                $doc['_item_number'] = $globalItemCounter;
                $matchingDocs[] = $doc;
                $totalRenderedDocs++;
            }

            if (!empty($matchingDocs) || !$isChunked) {
                $filteredSections[] = [
                    'key' => $sec['key'] ?? '',
                    'title' => $sec['title'] ?? '',
                    'detail_header' => $sec['detail_header'] ?? 'التفاصيل',
                    'totals' => $sec['totals'] ?? [],
                    'documents' => $matchingDocs,
                    'total_count' => count($secDocs),
                ];
            }
        }

        // 2. Pre-paginate into explicit page containers
        $pages = [];
        foreach ($filteredSections as $secIndex => $sec) {
            $docs = $sec['documents'];
            $secCount = count($docs);
            $docIdx = 0;

            if ($secCount === 0 && !$isChunked) {
                // Empty section
                $pages[] = [
                    'is_first_report_page' => (count($pages) === 0),
                    'is_first_sec_page' => true,
                    'is_last_sec_page' => true,
                    'section' => $sec,
                    'documents' => [],
                ];
                continue;
            }

            while ($docIdx < $secCount) {
                $isFirstReportPage = (count($pages) === 0);
                $isFirstSecPage = ($docIdx === 0);

                // Capacity calculation: Page 1 holds 32 rows, subsequent pages hold 38 rows
                $capacity = $isFirstReportPage ? 32 : 38;
                if (!$isFirstReportPage && $isFirstSecPage) {
                    $capacity = 36;
                }

                $remainingInSec = $secCount - $docIdx;
                $isLastSecPage = ($remainingInSec <= $capacity);

                if ($isLastSecPage && $remainingInSec > ($capacity - 6) && $remainingInSec > 10) {
                    $take = $capacity - 8;
                    $isLastSecPage = false;
                } else {
                    $take = min($capacity, $remainingInSec);
                }

                $pageDocs = array_slice($docs, $docIdx, $take);
                $docIdx += $take;

                $pages[] = [
                    'is_first_report_page' => $isFirstReportPage,
                    'is_first_sec_page' => $isFirstSecPage,
                    'is_last_sec_page' => $isLastSecPage,
                    'section' => $sec,
                    'documents' => $pageDocs,
                ];
            }
        }

        $totalPages = max(1, count($pages));
        $cntTotal = number_format($grandTotals['documents_count'] ?? 0);
        $grandTot = number_format($grandTotals['total'] ?? 0, 3);
        $logoHtml = $logoBase64 ? "<img src=\"{$logoBase64}\" style=\"max-height:44px;max-width:80px;object-fit:contain;\">" : "<div style=\"font-weight:900;color:#139625;font-size:11px;\">المدار الليبي<br><span style=\"color:#0284c7;\">للتأمين</span></div>";

        // 3. Render HTML for each page block
        $pagesHtml = '';
        foreach ($pages as $pIdx => $pageData) {
            $pageNum = $pIdx + 1;
            $sec = $pageData['section'];
            $secTitle = htmlspecialchars($sec['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $detailHeader = htmlspecialchars($sec['detail_header'] ?? 'التفاصيل', ENT_QUOTES, 'UTF-8');
            $docs = $pageData['documents'];

            // Header for this page
            $pageHeaderHtml = '';
            if ($pageData['is_first_report_page']) {
                $pageHeaderHtml = <<<PHDR
                <div class="hdr-full">
                    <div class="hdr-top">
                        <div>{$logoHtml}</div>
                        <div style="text-align:center;">
                            <h1>شركة المدار الليبي للتأمين</h1>
                            <div class="badge">كشف حوافظ الإنتاجية التفصيلي المعتمد</div>
                        </div>
                        <div style="width:60px;"></div>
                    </div>
                    <div class="meta-grid">
                        <div class="meta-cell"><span class="meta-lbl">نطاق الوكلاء والفروع:</span> <span class="meta-val">{$agentLabel}</span></div>
                        <div class="meta-cell"><span class="meta-lbl">الفترة المحددة:</span> <span class="meta-val" style="color:#0284c7;">{$periodLabel}</span></div>
                        <div class="meta-cell"><span class="meta-lbl">إجمالي الوثائق / المعروضة:</span> <span class="meta-val">{$cntTotal} (المعروض: {$totalRenderedDocs})</span></div>
                    </div>
                </div>
PHDR;
            } else {
                $pageHeaderHtml = <<<PHDR
                <div class="p-hdr-compact">
                    <span class="p-hdr-title">شركة المدار الليبي للتأمين - تابع كشف حوافظ الإنتاجية: {$secTitle}</span>
                    <span class="p-hdr-meta">{$periodLabel} | الوكلاء: {$agentLabel} | صفحة {$pageNum} من {$totalPages}</span>
                </div>
PHDR;
            }

            // Section Banner if starting a section
            $secBannerHtml = '';
            if ($pageData['is_first_sec_page'] || $pageData['is_first_report_page']) {
                $secCount = $sec['total_count'] ?? count($sec['documents']);
                $totSec = number_format($sec['totals']['total'] ?? 0, 3);
                $secBannerHtml = <<<SBANNER
                <div class="p-sec-banner">
                    <span>حوافظ إنتاجية: {$secTitle}</span>
                    <span>العدد: {$secCount} وثيقة | الإجمالي: {$totSec} د.ل</span>
                </div>
SBANNER;
            }

            // Rows HTML
            $rowsHtml = '';
            if (empty($docs)) {
                $rowsHtml = '<tr><td colspan="13" class="c-empty">لا توجد وثائق مسجلة في هذا القسم</td></tr>';
            } else {
                foreach ($docs as $doc) {
                    $itemNum = $doc['_item_number'] ?? '-';
                    $docNum = htmlspecialchars($doc['document_number'] ?? '', ENT_QUOTES, 'UTF-8');
                    $name = htmlspecialchars($doc['insured_name'] ?? '', ENT_QUOTES, 'UTF-8');
                    $date = htmlspecialchars($doc['issue_date'] ?? '', ENT_QUOTES, 'UTF-8');
                    $plate = htmlspecialchars($doc['plate_number'] ?? '-', ENT_QUOTES, 'UTF-8');
                    $prem = number_format($doc['premium'] ?? 0, 3);
                    $tax = number_format($doc['tax'] ?? 0, 3);
                    $sup = number_format($doc['supervision_fees'] ?? 0, 3);
                    $stamp = number_format($doc['stamp'] ?? 0, 3);
                    $iss = number_format($doc['issue_fees'] ?? 0, 3);
                    $detail = htmlspecialchars($doc['extra_detail'] ?? '-', ENT_QUOTES, 'UTF-8');
                    $tot = number_format($doc['total'] ?? 0, 3);
                    $agency = htmlspecialchars($doc['agency_name'] ?? ($doc['user_name'] ?? '-'), ENT_QUOTES, 'UTF-8');

                    $rowsHtml .= "<tr><td>{$itemNum}</td><td class=\"c-doc\">{$docNum}</td><td class=\"c-name\">{$name}</td><td>{$date}</td><td>{$plate}</td><td class=\"c-num\">{$prem}</td><td class=\"c-num\">{$tax}</td><td class=\"c-num\">{$sup}</td><td class=\"c-num\">{$stamp}</td><td class=\"c-num\">{$iss}</td><td class=\"c-det\">{$detail}</td><td class=\"c-tot\">{$tot}</td><td class=\"c-agency\">{$agency}</td></tr>";
                }
            }

            // Section Summary if this is the last page of the section
            $secSummaryHtml = '';
            if ($pageData['is_last_sec_page']) {
                $premSec = number_format($sec['totals']['premium'] ?? 0, 3);
                $taxSec = number_format($sec['totals']['tax'] ?? 0, 3);
                $supSec = number_format($sec['totals']['supervision_fees'] ?? 0, 3);
                $stampSec = number_format($sec['totals']['stamp'] ?? 0, 3);
                $issSec = number_format($sec['totals']['issue_fees'] ?? 0, 3);
                $totSec = number_format($sec['totals']['total'] ?? 0, 3);

                $secSummaryHtml = <<<SSUM
                <div class="p-sum-box">
                    <table class="p-sum-tbl">
                        <tr>
                            <th>القسط الصافي</th><td>{$premSec} د.ل</td>
                            <th>الضريبة</th><td>{$taxSec} د.ل</td>
                            <th>إشراف ورقابة</th><td>{$supSec} د.ل</td>
                            <th>الدمغة وم. الإصدار</th><td>{$stampSec} + {$issSec} د.ل</td>
                            <th class="tot-th">إجمالي القسم</th><td class="tot-td">{$totSec} د.ل</td>
                        </tr>
                    </table>
                </div>
SSUM;
            }

            $pagesHtml .= <<<PAGEBLOCK
            <div class="p-page">
                <div class="p-page-top">
                    {$pageHeaderHtml}
                    {$secBannerHtml}
                    <table class="p-tbl">
                        <colgroup>
                            <col style="width:3%;">
                            <col style="width:10%;">
                            <col style="width:18%;">
                            <col style="width:7%;">
                            <col style="width:8%;">
                            <col style="width:7%;">
                            <col style="width:6%;">
                            <col style="width:6%;">
                            <col style="width:5%;">
                            <col style="width:6%;">
                            <col style="width:8%;">
                            <col style="width:8%;">
                            <col style="width:8%;">
                        </colgroup>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>رقم الوثيقة</th>
                                <th>اسم المؤمن له</th>
                                <th>تاريخ الإصدار</th>
                                <th>رقم اللوحة</th>
                                <th>القسط الصافي</th>
                                <th>الضريبة</th>
                                <th>أ. ورقابة</th>
                                <th>الدمغة</th>
                                <th>م. الإصدار</th>
                                <th>{$detailHeader}</th>
                                <th>الإجمالي</th>
                                <th>الوكالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$rowsHtml}
                        </tbody>
                    </table>
                    {$secSummaryHtml}
                </div>
                <div class="p-ftr">
                    <span>منظومة شركة المدار الليبي للتأمين - تقرير الحوافظ التفصيلي</span>
                    <span>صفحة {$pageNum} من {$totalPages}</span>
                    <span>تاريخ الطباعة: {$dateStr}</span>
                </div>
            </div>
PAGEBLOCK;
        }

        // Top Chunk Navigation Bar (Screen Only)
        $chunkNavHtml = '';
        if ($totalDocsCount > 1500) {
            $totalChunks = ceil($totalDocsCount / $chunkSize);
            $chunkBtns = '';
            for ($i = 1; $i <= $totalChunks; $i++) {
                $cFrom = ($i - 1) * $chunkSize + 1;
                $cTo = min($i * $chunkSize, $totalDocsCount);
                $activeCls = ($isChunked && $chunkPage == $i) ? 'btn-active' : 'btn-normal';
                $cUrl = $request->fullUrlWithQuery(['chunk' => $i, 'chunk_size' => $chunkSize, 'force_all' => '1']);
                $chunkBtns .= "<a href=\"{$cUrl}\" class=\"chunk-btn {$activeCls}\">جزء {$i} ({$cFrom}-{$cTo})</a> ";
            }
            $allActive = (!$isChunked) ? 'btn-active' : 'btn-normal';
            $allUrl = $request->fullUrlWithQuery(['force_all' => '1', 'chunk' => 'all']);

            $chunkNavHtml = <<<CHUNKS
            <div class="no-print chunk-bar">
                <div class="chunk-title">
                    ⚡ <strong>تجزئة الطباعة الفائقة:</strong>
                    <span>اختر حزمة لتوليد معاينة الطباعة فوراً في ثانية واحدة، أو اطبع كافة الـ {$totalDocsCount} وثيقة مقسمة تلقائياً إلى {$totalPages} صفحة:</span>
                </div>
                <div class="chunk-btns">
                    <a href="{$allUrl}" class="chunk-btn {$allActive}">عرض كافة الصفحات ({$totalDocsCount} وثيقة - {$totalPages} صفحة)</a>
                    {$chunkBtns}
                </div>
                <div class="chunk-actions">
                    <button onclick="window.print()" class="p-btn">🖨️ طباعة المستند الآن</button>
                    <a href="{$request->fullUrlWithQuery(['print_mode' => 'summary'])}" class="s-btn">📄 طباعة كشف الملخص المالي المعتمد (صفحة واحدة)</a>
                </div>
            </div>
CHUNKS;
        }

        $shouldAutoPrint = ($isChunked || $totalDocsCount <= 500) && !$request->boolean('no_auto_print');
        $shouldAutoPrintJson = $shouldAutoPrint ? 'true' : 'false';

        return <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>كشف حوافظ الإنتاجية التفصيلي - شركة المدار الليبي للتأمين</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 5mm 6mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Tahoma, Arial, sans-serif;
            font-size: 7.5pt;
            color: #0f172a;
            background: #525659;
            direction: rtl;
            line-height: 1.15;
        }
        @media screen {
            body {
                padding: 15px;
            }
            .p-page {
                background: #fff;
                width: 297mm;
                min-height: 198mm;
                margin: 0 auto 16px auto;
                padding: 6mm 8mm;
                box-shadow: 0 4px 15px rgba(0,0,0,0.25);
                border-radius: 4px;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
            }
        }
        @media print {
            .no-print { display: none !important; }
            html, body {
                width: 297mm !important;
                height: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
            }
            .p-page {
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                width: 100% !important;
                height: 198mm !important;
                max-height: 198mm !important;
                overflow: hidden !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                padding: 0 !important;
                margin: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                border: none !important;
            }
            .p-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }
        }

        .hdr-full {
            border-bottom: 2px solid #0284c7;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .hdr-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .hdr-top h1 {
            font-size: 14px;
            color: #0284c7;
            font-weight: 900;
        }
        .badge {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 2px 14px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
            display: inline-block;
            margin-top: 1px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 3px 6px;
        }
        .meta-cell {
            font-size: 8.5pt;
            text-align: center;
            border-left: 1px solid #cbd5e1;
        }
        .meta-cell:last-child { border-left: none; }
        .meta-lbl { color: #64748b; font-weight: 700; margin-left: 4px; }
        .meta-val { font-weight: 800; color: #0f172a; }

        .p-hdr-compact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #0284c7;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        .p-hdr-title { font-size: 8pt; font-weight: 800; color: #0284c7; }
        .p-hdr-meta { font-size: 7.5pt; color: #475569; font-weight: 700; }

        .p-sec-banner {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 3px;
            padding: 2px 6px;
            margin-bottom: 3px;
            display: flex;
            justify-content: space-between;
            font-weight: 800;
            font-size: 8pt;
            color: #0369a1;
        }

        .p-tbl {
            width: 100%;
            table-layout: fixed !important;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 7.5pt;
            text-align: center;
            border-top: 1px solid #94a3b8;
            border-right: 1px solid #94a3b8;
        }
        .p-tbl th {
            background: #e2e8f0;
            color: #0f172a;
            font-weight: 800;
            border-bottom: 1px solid #94a3b8;
            border-left: 1px solid #94a3b8;
            padding: 2px 2px;
            font-size: 7.5pt;
            height: 18px;
            white-space: nowrap;
            overflow: hidden;
        }
        .p-tbl td {
            border-bottom: 1px solid #cbd5e1;
            border-left: 1px solid #cbd5e1;
            padding: 1px 2px;
            height: 15.5px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: 7.5pt;
            color: #0f172a;
        }
        .p-tbl tr:nth-child(even) td {
            background: #f8fafc;
        }
        .c-doc { font-weight: 800; color: #0369a1; font-family: Tahoma, monospace; font-size: 7.5pt; }
        .c-name { text-align: right !important; padding-right: 4px !important; font-weight: 600; }
        .c-num { font-family: Tahoma, monospace; font-size: 7.5pt; }
        .c-tot { font-weight: 900; color: #15803d; font-family: Tahoma, monospace; font-size: 8pt; }
        .c-det { font-size: 7.5pt; color: #475569; }
        .c-agency { font-size: 7.5pt; }
        .c-empty { padding: 10px; color: #94a3b8; font-weight: 700; }

        .p-sum-box {
            margin-top: 3px;
            display: flex;
            justify-content: center;
        }
        .p-sum-tbl {
            width: 85%;
            border-collapse: collapse;
            font-size: 7.5pt;
            text-align: center;
            border: 1px solid #94a3b8;
        }
        .p-sum-tbl th {
            background: #f1f5f9;
            padding: 2px 4px;
            border: 1px solid #cbd5e1;
            font-weight: 700;
        }
        .p-sum-tbl td {
            background: #fff;
            padding: 2px 4px;
            border: 1px solid #cbd5e1;
            font-weight: 800;
            color: #0369a1;
        }
        .tot-th { background: #0284c7 !important; color: #fff !important; }
        .tot-td { color: #15803d !important; font-size: 8.5pt !important; }

        .p-ftr {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #cbd5e1;
            padding-top: 2px;
            font-size: 7.5pt;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }

        .chunk-bar {
            background: #f8fafc;
            border: 1.5px solid #0284c7;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .chunk-title { font-size: 12px; color: #1e293b; display: flex; align-items: center; gap: 8px; }
        .chunk-btns { display: flex; flex-wrap: wrap; gap: 6px; }
        .chunk-btn {
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-active { background: #0284c7; color: #fff; }
        .btn-normal { background: #e2e8f0; color: #334155; }
        .btn-normal:hover { background: #cbd5e1; }
        .chunk-actions { display: flex; gap: 8px; margin-top: 4px; }
        .p-btn { background: #10b981; color: #fff; border: none; padding: 6px 16px; border-radius: 6px; font-weight: 800; font-size: 12px; cursor: pointer; font-family: inherit; }
        .s-btn { background: #f0f9ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 6px; font-weight: 800; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center; }
    </style>
</head>
<body>
    {$chunkNavHtml}
    {$pagesHtml}

    <script>
        window.addEventListener('load', function() {
            var shouldAutoPrint = {$shouldAutoPrintJson};
            if (shouldAutoPrint) {
                setTimeout(function() {
                    window.print();
                }, 800);
            }
        });
    </script>
</body>
</html>
HTML;
    }
}


