<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class AgentPaymentHelper
{
    /**
     * Get total amount paid by an agent across payment sources (إدارة الإيرادات + تسديدات الإقفالات).
     *
     * @param int $agentId
     * @return float
     */
    public static function getTotalPaid(int $agentId): float
    {
        $schema = DB::getSchemaBuilder();

        // 1. Payment Vouchers (إيصالات القبض في إدارة الإيرادات)
        $vouchersPaid = 0.0;
        $linkedClosureIds = [];
        $linkedYearMonths = [];

        if ($schema->hasTable('payment_vouchers')) {
            $vouchers = DB::table('payment_vouchers')
                ->where('branch_agent_id', $agentId)
                ->get();

            foreach ($vouchers as $v) {
                $vouchersPaid += (float)$v->amount;

                $extra = is_string($v->extra_details) ? json_decode($v->extra_details, true) : (array)($v->extra_details ?? []);
                if (!empty($extra['closure_id'])) {
                    $linkedClosureIds[$extra['closure_id']] = true;
                }
                if (!empty($extra['year']) && !empty($extra['month'])) {
                    $linkedYearMonths[(int)$extra['year'] . '-' . (int)$extra['month']] = true;
                }
            }
        }

        // 2. Monthly Account Closures with paid_amount where no Payment Voucher exists
        $closuresPaid = 0.0;
        if ($schema->hasTable('monthly_account_closures')) {
            $closures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->where('paid_amount', '>', 0)
                ->get();

            foreach ($closures as $c) {
                $hasClosureId = isset($linkedClosureIds[$c->id]);
                $hasYearMonth = isset($linkedYearMonths[(int)$c->year . '-' . (int)$c->month]);

                if (!$hasClosureId && !$hasYearMonth) {
                    $closuresPaid += (float)$c->paid_amount;
                }
            }
        }

        return round($vouchersPaid + $closuresPaid, 2);
    }

    /**
     * Get all payment items for an agent tagged with month_key for monthly ledgers & account statements.
     * Only counts Payment Vouchers from Revenue Management (إدارة الإيرادات) and Closures.
     *
     * @param int $agentId
     * @return array
     */
    public static function getAllPayments(int $agentId): array
    {
        $schema = DB::getSchemaBuilder();
        $allPayments = [];
        $linkedClosureIds = [];
        $linkedYearMonths = [];

        // 1. Payment Vouchers (إدارة الإيرادات)
        if ($schema->hasTable('payment_vouchers')) {
            $vouchers = DB::table('payment_vouchers')
                ->where('branch_agent_id', $agentId)
                ->get();

            foreach ($vouchers as $v) {
                $extra = is_string($v->extra_details) ? json_decode($v->extra_details, true) : (array)($v->extra_details ?? []);
                if (!empty($extra['closure_id'])) {
                    $linkedClosureIds[$extra['closure_id']] = true;
                }
                if (!empty($extra['year']) && !empty($extra['month'])) {
                    $linkedYearMonths[(int)$extra['year'] . '-' . (int)$extra['month']] = true;
                }

                $year = $extra['year'] ?? null;
                $month = $extra['month'] ?? null;
                $date = $v->payment_date ?? $v->created_at ?? date('Y-m-d');

                $mKey = ($year && $month)
                    ? sprintf('%04d-%02d', (int)$year, (int)$month)
                    : \Carbon\Carbon::parse($date)->format('Y-m');

                $allPayments[] = [
                    'amount'       => (float)$v->amount,
                    'payment_date' => $date,
                    'month_key'    => $mKey,
                    'source'       => 'payment_voucher',
                    'id'           => $v->id,
                ];
            }
        }

        // 2. Closures with paid_amount without Payment Voucher
        if ($schema->hasTable('monthly_account_closures')) {
            $closures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->where('paid_amount', '>', 0)
                ->get();

            foreach ($closures as $c) {
                $hasClosureId = isset($linkedClosureIds[$c->id]);
                $hasYearMonth = isset($linkedYearMonths[(int)$c->year . '-' . (int)$c->month]);

                if (!$hasClosureId && !$hasYearMonth) {
                    $mKey = sprintf('%04d-%02d', (int)$c->year, (int)$c->month);
                    $allPayments[] = [
                        'amount'       => (float)$c->paid_amount,
                        'payment_date' => "{$c->year}-" . sprintf('%02d', $c->month) . "-01",
                        'month_key'    => $mKey,
                        'source'       => 'closure',
                        'id'           => $c->id,
                    ];
                }
            }
        }

        return $allPayments;
    }
}
