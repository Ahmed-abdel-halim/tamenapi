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

            // Load existing closures for this agent (keyed by YYYY-MM)
            $existingClosures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->whereNotNull('year')
                ->whereNotNull('month')
                ->get()
                ->keyBy(function ($row) {
                    return $row->year . '-' . str_pad($row->month, 2, '0', STR_PAD_LEFT);
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
                        if ($docDate->copy()->startOfMonth() <= \Carbon\Carbon::now()->startOfMonth() && $docDate->copy()->startOfMonth() >= $startDate) {
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
                if (!isset($months[$mk])) {
                    if ($firstMonthKey && $mk < $firstMonthKey) {
                        $mk = $firstMonthKey;
                    } elseif ($lastMonthKey && $mk > $lastMonthKey) {
                        $mk = $lastMonthKey;
                    }
                }
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

                $dueAmount = round($m['agent_share'], 2);

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
                    'agent_share'     => $dueAmount,
                    'company_share'   => round($m['company_share'], 2),
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
                $grandTotalAgentShare   += $dueAmount;
                $grandTotalCompanyShare += $m['company_share'];
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

            $fromDate = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->format('Y-m-d');
            $toDate   = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth()->format('Y-m-d');

            $closure = \App\Models\MonthlyAccountClosure::firstOrCreate(
                [
                    'branch_agent_id' => $validated['branch_agent_id'],
                    'year'            => $validated['year'],
                    'month'           => $validated['month'],
                ],
                [
                    'from_date'        => $fromDate,
                    'to_date'          => $toDate,
                    'due_amount'       => 0,
                    'paid_amount'      => 0,
                    'remaining_amount' => 0,
                    'is_audited'       => false,
                ]
            );

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
                'branch_agent_id' => 'required|integer|exists:branches_agents,id',
                'year'            => 'required|integer',
                'month'           => 'required|integer|min:1|max:12',
                'paid_amount'     => 'required|numeric|min:0',
                'due_amount'      => 'required|numeric|min:0',
                'payment_amount'  => 'nullable|numeric|min:0',
                'notes'           => 'nullable|string|max:500',
            ]);

            $fromDate = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->format('Y-m-d');
            $toDate   = \Carbon\Carbon::create($validated['year'], $validated['month'], 1)->endOfMonth()->format('Y-m-d');
            $remaining = round((float)$validated['due_amount'] - (float)$validated['paid_amount'], 2);

            $existingClosure = \App\Models\MonthlyAccountClosure::where('branch_agent_id', $validated['branch_agent_id'])
                ->where('year', $validated['year'])
                ->where('month', $validated['month'])
                ->first();

            $previousPaidAmount = $existingClosure ? (float)$existingClosure->paid_amount : 0;

            $closure = \App\Models\MonthlyAccountClosure::updateOrCreate(
                [
                    'branch_agent_id' => $validated['branch_agent_id'],
                    'year'            => $validated['year'],
                    'month'           => $validated['month'],
                ],
                [
                    'from_date'        => $fromDate,
                    'to_date'          => $toDate,
                    'due_amount'       => $validated['due_amount'],
                    'paid_amount'      => $validated['paid_amount'],
                    'remaining_amount' => max(0, $remaining),
                    'notes'            => $validated['notes'] ?? null,
                ]
            );

            // Amount paid in this specific action
            $newPaymentAmount = isset($validated['payment_amount']) && (float)$validated['payment_amount'] > 0
                ? (float)$validated['payment_amount']
                : round((float)$validated['paid_amount'] - $previousPaidAmount, 2);

            $paymentVoucher = null;
            if ($newPaymentAmount > 0) {
                $agent = \App\Models\BranchAgent::find($validated['branch_agent_id']);
                $agencyName = $agent ? ($agent->agency_name ?? ($agent->agent_name ?? "وكيل #{$agent->id}")) : 'وكيل';

                $monthNames = [1=>'يناير', 2=>'فبراير', 3=>'مارس', 4=>'أبريل', 5=>'مايو', 6=>'يونيو', 7=>'يوليو', 8=>'أغسطس', 9=>'سبتمبر', 10=>'أكتوبر', 11=>'نوفمبر', 12=>'ديسمبر'];
                $monthLabel = ($monthNames[$validated['month']] ?? $validated['month']) . ' ' . $validated['year'];

                // Generate unique Payment Voucher number
                $voucherNumber = 'PV-' . date('Y') . '-' . rand(1000, 9999);
                while (\App\Models\PaymentVoucher::where('voucher_number', $voucherNumber)->exists()) {
                    $voucherNumber = 'PV-' . date('Y') . '-' . rand(1000, 9999);
                }

                // 1. Create Payment Voucher (إيصال قبض مالي في قسم إدارة الإيرادات)
                $paymentVoucher = \App\Models\PaymentVoucher::create([
                    'voucher_number'   => $voucherNumber,
                    'branch_agent_id'  => $validated['branch_agent_id'],
                    'amount'           => $newPaymentAmount,
                    'payment_method'   => 'نقدي',
                    'payment_date'     => date('Y-m-d'),
                    'notes'            => "تسديد دفعة كشف حساب شهري ({$monthLabel})" . ($validated['notes'] ? " - {$validated['notes']}" : ''),
                    'extra_details'    => [
                        'type'       => 'monthly_account_closure',
                        'year'       => $validated['year'],
                        'month'      => $validated['month'],
                        'closure_id' => $closure->id,
                    ]
                ]);

                // 2. Create Treasury Transaction (معاملة مقبوضات في خزينة الإيرادات)
                \App\Models\TreasuryTransaction::create([
                    'transaction_date' => date('Y-m-d'),
                    'type'             => 'income',
                    'amount'           => $newPaymentAmount,
                    'description'      => "تسديد كشف حساب شهري - {$agencyName} - شهر {$monthLabel}",
                    'source'           => $agencyName,
                    'reference_number' => $voucherNumber,
                    'branch_agent_id'  => $validated['branch_agent_id'],
                    'payment_source'   => 'نقدي',
                    'notes'            => $validated['notes'] ?? null,
                ]);
            }

            return response()->json([
                'success'         => true,
                'message'         => 'تم تسجيل الدفعة وإنشاء إيصال القبض في إدارة الإيرادات والخزينة بنجاح',
                'closure'         => $closure,
                'payment_voucher' => $paymentVoucher,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الدفعة',
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
}
