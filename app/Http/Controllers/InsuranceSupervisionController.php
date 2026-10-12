<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\InsuranceSupervisionPayment;
use App\Models\InsuranceSupervisionSetting;
use App\Models\InsuranceDocument;
use App\Models\InternationalInsuranceDocument;
use App\Models\MarineStructureInsuranceDocument;
use App\Models\TravelInsuranceDocument;
use App\Models\ResidentInsuranceDocument;
use App\Models\PersonalAccidentInsuranceDocument;
use App\Models\ProfessionalLiabilityInsuranceDocument;
use App\Models\CargoInsuranceDocument;
use App\Models\CashInTransitInsuranceDocument;
use App\Models\SchoolStudentInsuranceDocument;

class InsuranceSupervisionController extends Controller
{
    /**
     * Return list of supported insurance modules and their config.
     */
    protected function getModuleMap()
    {
        return [
            'vehicle' => [
                'name' => 'تأمين المركبات الآلية (إجباري / شامل)',
                'model' => InsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'international' => [
                'name' => 'تأمين البطاقة البرتقالية (دولي)',
                'model' => InternationalInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'document_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'marine' => [
                'name' => 'التأمين البحري وأجسام السفن',
                'model' => MarineStructureInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'travel' => [
                'name' => 'تأمين السفر بالخارج',
                'model' => TravelInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'resident' => [
                'name' => 'تأمين الرعاية الصحية للمقيمين والأجانب',
                'model' => ResidentInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'personal_accident' => [
                'name' => 'تأمين الحوادث الشخصية',
                'model' => PersonalAccidentInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'professional_liability' => [
                'name' => 'تأمين المسؤولية المهنية والمدنية',
                'model' => ProfessionalLiabilityInsuranceDocument::class,
                'date_col' => 'issue_date',
                'fee_col' => 'supervision_fees',
                'premium_col' => 'total',
                'number_col' => 'insurance_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'cargo' => [
                'name' => 'تأمين نقل البضائع',
                'model' => CargoInsuranceDocument::class,
                'date_col' => 'created_at',
                'fee_col' => null,
                'premium_col' => 'premium_amount',
                'number_col' => 'policy_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'cash_in_transit' => [
                'name' => 'تأمين نقل الأموال',
                'model' => CashInTransitInsuranceDocument::class,
                'date_col' => 'created_at',
                'fee_col' => null,
                'premium_col' => 'premium_amount',
                'number_col' => 'policy_number',
                'insured_col' => 'insured_name',
                'branch_rel' => 'branchAgent',
            ],
            'school_student' => [
                'name' => 'تأمين طلبة المدارس',
                'model' => SchoolStudentInsuranceDocument::class,
                'date_col' => 'created_at',
                'fee_col' => null,
                'premium_col' => 'premium_amount',
                'number_col' => 'policy_number',
                'insured_col' => 'school_name',
                'branch_rel' => 'branchAgent',
            ],
        ];
    }

    /**
     * Dashboard financial summary: Total Due vs Total Paid vs Remaining
     */
    public function dashboardSummary(Request $request)
    {
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $year = $request->input('year');
        $month = $request->input('month');

        $settings = InsuranceSupervisionSetting::all()->keyBy('insurance_type_key');
        $modules = $this->getModuleMap();

        $categoryBreakdown = [];
        $totalSupervisionDue = 0.0;
        $totalPremiums = 0.0;
        $totalDocumentsCount = 0;

        foreach ($modules as $key => $meta) {
            $setting = $settings->get($key);
            $rate = $setting ? (float)$setting->fee_rate_percentage : 1.0;
            $fixed = $setting ? (float)$setting->fixed_fee_amount : 0.0;

            $modelClass = $meta['model'];
            $instance = new $modelClass;
            $tableName = $instance->getTable();
            $query = $modelClass::query();

            // Exclude canceled if column exists
            try {
                if (\Schema::hasColumn($tableName, 'is_canceled')) {
                    $query->where('is_canceled', false);
                }
            } catch (\Exception $e) {}

            $dateCol = \Schema::hasColumn($tableName, $meta['date_col']) ? $meta['date_col'] : 'created_at';

            // Apply date filters
            if ($fromDate) {
                $query->whereDate($dateCol, '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate($dateCol, '<=', $toDate);
            }
            if ($year && $year !== 'الكل') {
                $query->whereYear($dateCol, $year);
            }
            if ($month && $month !== 'الكل') {
                $query->whereMonth($dateCol, $month);
            }

            $count = (int)$query->count();
            $docTotalPremium = 0.0;
            $docSupervisionFees = 0.0;

            if ($count > 0) {
                $premCol = (\Schema::hasColumn($tableName, $meta['premium_col'])) ? $meta['premium_col'] : (\Schema::hasColumn($tableName, 'premium') ? 'premium' : null);
                if ($premCol) {
                    $docTotalPremium = (float)$query->sum($premCol);
                }

                if ($meta['fee_col'] && \Schema::hasColumn($tableName, $meta['fee_col'])) {
                    $docSupervisionFees = (float)$query->sum($meta['fee_col']);
                } else {
                    $docSupervisionFees = ($docTotalPremium * ($rate / 100.0)) + ($fixed * $count);
                }
            }

            $totalDocumentsCount += $count;
            $totalPremiums += $docTotalPremium;
            $totalSupervisionDue += $docSupervisionFees;

            $categoryBreakdown[] = [
                'key' => $key,
                'name' => $meta['name'],
                'document_count' => $count,
                'total_premium' => round($docTotalPremium, 2),
                'supervision_fee' => round($docSupervisionFees, 2),
                'fee_rate' => $rate,
                'fixed_fee' => $fixed,
            ];
        }

        // Payments to supervision authority
        $paymentsQuery = InsuranceSupervisionPayment::query();
        if ($fromDate) {
            $paymentsQuery->whereDate('payment_date', '>=', $fromDate);
        }
        if ($toDate) {
            $paymentsQuery->whereDate('payment_date', '<=', $toDate);
        }
        if ($year && $year !== 'الكل') {
            $paymentsQuery->whereYear('payment_date', $year);
        }
        if ($month && $month !== 'الكل') {
            $paymentsQuery->whereMonth('payment_date', $month);
        }

        $totalPaid = (float)$paymentsQuery->sum('amount');
        $paymentsCount = (int)$paymentsQuery->count();
        $remainingDue = $totalSupervisionDue - $totalPaid;

        return response()->json([
            'success' => true,
            'summary' => [
                'total_supervision_due' => round($totalSupervisionDue, 2),
                'total_paid_to_authority' => round($totalPaid, 2),
                'remaining_due_to_authority' => round($remainingDue, 2),
                'total_premiums' => round($totalPremiums, 2),
                'total_documents_count' => $totalDocumentsCount,
                'payments_count' => $paymentsCount,
            ],
            'categories' => $categoryBreakdown,
        ]);
    }

    /**
     * Detailed paginated list of policies and their supervision fees.
     */
    public function detailedReport(Request $request)
    {
        $insuranceType = $request->input('insurance_type', 'all');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $search = trim($request->input('search', ''));
        $perPage = (int)$request->input('per_page', 25);
        $page = (int)$request->input('page', 1);

        $modules = $this->getModuleMap();
        $settings = InsuranceSupervisionSetting::all()->keyBy('insurance_type_key');

        $activeModules = [];
        if ($insuranceType !== 'all' && isset($modules[$insuranceType])) {
            $activeModules[$insuranceType] = $modules[$insuranceType];
        } else {
            $activeModules = $modules;
        }

        $allRows = [];

        foreach ($activeModules as $key => $meta) {
            $modelClass = $meta['model'];
            $instance = new $modelClass;
            $tableName = $instance->getTable();
            $query = $modelClass::query();

            if ($meta['branch_rel'] && method_exists($modelClass, $meta['branch_rel'])) {
                $query->with([$meta['branch_rel']]);
            }

            try {
                if (\Schema::hasColumn($tableName, 'is_canceled')) {
                    $query->where('is_canceled', false);
                }
            } catch (\Exception $e) {}

            $dateCol = \Schema::hasColumn($tableName, $meta['date_col']) ? $meta['date_col'] : 'created_at';

            if ($fromDate) {
                $query->whereDate($dateCol, '>=', $fromDate);
            }
            if ($toDate) {
                $query->whereDate($dateCol, '<=', $toDate);
            }

            $numCol = \Schema::hasColumn($tableName, $meta['number_col']) ? $meta['number_col'] : null;
            $insCol = \Schema::hasColumn($tableName, $meta['insured_col']) ? $meta['insured_col'] : null;

            if ($search !== '') {
                $query->where(function($q) use ($numCol, $insCol, $search) {
                    if ($numCol && $insCol) {
                        $q->where($numCol, 'like', "%{$search}%")->orWhere($insCol, 'like', "%{$search}%");
                    } elseif ($numCol) {
                        $q->where($numCol, 'like', "%{$search}%");
                    } elseif ($insCol) {
                        $q->where($insCol, 'like', "%{$search}%");
                    }
                });
            }

            $setting = $settings->get($key);
            $rate = $setting ? (float)$setting->fee_rate_percentage : 1.0;
            $fixed = $setting ? (float)$setting->fixed_fee_amount : 0.0;

            // Fetch recent 300 per category to allow fast merging across 10 tables
            $items = $query->orderBy($dateCol, 'desc')->limit(300)->get();

            $premCol = (\Schema::hasColumn($tableName, $meta['premium_col'])) ? $meta['premium_col'] : (\Schema::hasColumn($tableName, 'premium') ? 'premium' : null);

            foreach ($items as $item) {
                $premium = 0.0;
                if ($premCol && isset($item->{$premCol})) {
                    $premium = (float)$item->{$premCol};
                }

                $fee = 0.0;
                if ($meta['fee_col'] && isset($item->{$meta['fee_col']}) && $item->{$meta['fee_col']} !== null) {
                    $fee = (float)$item->{$meta['fee_col']};
                } else {
                    $fee = ($premium * ($rate / 100.0)) + $fixed;
                }

                $issueDate = $item->{$dateCol} ?? $item->created_at;
                if ($issueDate instanceof \DateTimeInterface) {
                    $issueDate = $issueDate->format('Y-m-d');
                } else {
                    $issueDate = substr((string)$issueDate, 0, 10);
                }

                $branchName = null;
                if ($meta['branch_rel'] && $item->relationLoaded($meta['branch_rel']) && $item->{$meta['branch_rel']}) {
                    $branch = $item->{$meta['branch_rel']};
                    $branchName = $branch->name ?? $branch->agent_name ?? $branch->branch_name ?? null;
                }

                $allRows[] = [
                    'id' => $item->id,
                    'module_key' => $key,
                    'insurance_type_name' => $meta['name'],
                    'policy_number' => ($numCol ? $item->{$numCol} : null) ?: ('#' . $item->id),
                    'insured_name' => ($insCol ? $item->{$insCol} : null) ?: 'عميل',
                    'issue_date' => $issueDate,
                    'premium' => round($premium, 2),
                    'supervision_fee' => round($fee, 2),
                    'fee_rate' => $rate,
                    'branch_name' => $branchName ?? 'الفرع الرئيسي',
                ];
            }
        }

        // Sort all rows by issue_date desc
        usort($allRows, function($a, $b) {
            return strcmp($b['issue_date'], $a['issue_date']);
        });

        $totalItems = count($allRows);
        $totalSupervisionSum = array_sum(array_column($allRows, 'supervision_fee'));
        $totalPremiumsSum = array_sum(array_column($allRows, 'premium'));

        $slice = array_slice($allRows, ($page - 1) * $perPage, $perPage);

        return response()->json([
            'success' => true,
            'data' => $slice,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalItems,
                'total_pages' => ceil($totalItems / max(1, $perPage)),
            ],
            'aggregates' => [
                'total_supervision_fee' => round($totalSupervisionSum, 2),
                'total_premium' => round($totalPremiumsSum, 2),
            ],
        ]);
    }

    /**
     * Get payments made to the Authority.
     */
    public function getPayments(Request $request)
    {
        $payments = InsuranceSupervisionPayment::orderBy('payment_date', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $payments,
            'total_paid' => round((float)$payments->sum('amount'), 2),
        ]);
    }

    /**
     * Store a payment made to the Authority.
     */
    public function storePayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'voucher_number' => 'nullable|string',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,pdf,webp|max:10240',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_image')) {
            $file = $request->file('receipt_image');
            $filename = 'supervision_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('supervision_receipts', $filename, 'public');
            $receiptPath = '/storage/' . $path;
        }

        $payment = InsuranceSupervisionPayment::create([
            'voucher_number' => $request->voucher_number ?: ('SUP-' . date('Ymd') . '-' . rand(100, 999)),
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'bank_name' => $request->bank_name,
            'check_number' => $request->check_number,
            'period_from' => $request->period_from ?: null,
            'period_to' => $request->period_to ?: null,
            'receipt_image' => $receiptPath,
            'notes' => $request->notes,
            'created_by' => auth()->id() ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل سند السداد بنجاح',
            'data' => $payment,
        ]);
    }

    /**
     * Update a payment record.
     */
    public function updatePayment(Request $request, $id)
    {
        $payment = InsuranceSupervisionPayment::findOrFail($id);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
        ]);

        $receiptPath = $payment->receipt_image;
        if ($request->hasFile('receipt_image')) {
            $file = $request->file('receipt_image');
            $filename = 'supervision_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('supervision_receipts', $filename, 'public');
            $receiptPath = '/storage/' . $path;
        }

        $payment->update([
            'voucher_number' => $request->voucher_number ?: $payment->voucher_number,
            'payment_date' => $request->payment_date,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'bank_name' => $request->bank_name,
            'check_number' => $request->check_number,
            'period_from' => $request->period_from ?: null,
            'period_to' => $request->period_to ?: null,
            'receipt_image' => $receiptPath,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث سند السداد بنجاح',
            'data' => $payment,
        ]);
    }

    /**
     * Delete a payment record.
     */
    public function deletePayment($id)
    {
        $payment = InsuranceSupervisionPayment::findOrFail($id);
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف سند السداد بنجاح',
        ]);
    }

    /**
     * Get settings
     */
    public function getSettings()
    {
        $settings = InsuranceSupervisionSetting::all();
        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $items = $request->input('settings', []);
        foreach ($items as $item) {
            if (isset($item['insurance_type_key'])) {
                InsuranceSupervisionSetting::updateOrCreate(
                    ['insurance_type_key' => $item['insurance_type_key']],
                    [
                        'insurance_type_name' => $item['insurance_type_name'] ?? '',
                        'fee_rate_percentage' => $item['fee_rate_percentage'] ?? 1.0,
                        'fixed_fee_amount' => $item['fixed_fee_amount'] ?? 0.0,
                        'is_active' => $item['is_active'] ?? true,
                        'notes' => $item['notes'] ?? null,
                    ]
                );
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ إعدادات نسب هيئة الإشراف بنجاح',
            'data' => InsuranceSupervisionSetting::all(),
        ]);
    }
}
