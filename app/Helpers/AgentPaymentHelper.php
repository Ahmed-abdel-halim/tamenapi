<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class AgentPaymentHelper
{
    /**
     * Get total amount paid by an agent across all payment sources without duplicate counting.
     *
     * @param int $agentId
     * @return float
     */
    public static function getTotalPaid(int $agentId): float
    {
        $schema = DB::getSchemaBuilder();

        // 1. Payment Vouchers (إيصالات القبض في إدارة الإيرادات)
        $vouchersPaid = 0.0;
        if ($schema->hasTable('payment_vouchers')) {
            $vouchersPaid = (float)DB::table('payment_vouchers')
                ->where('branch_agent_id', $agentId)
                ->sum('amount');
        }

        // 2. Approved Agent Transfers without a Payment Voucher (الحوالات المعتمدة)
        $transfersPaid = 0.0;
        if ($schema->hasTable('agent_transfers')) {
            $transfersPaid = (float)DB::table('agent_transfers')
                ->where('branch_agent_id', $agentId)
                ->where('status', 'approved')
                ->whereNull('payment_voucher_id')
                ->sum('amount');
        }

        // 3. Monthly Account Closures with paid_amount where no Payment Voucher exists
        $closuresPaid = 0.0;
        if ($schema->hasTable('monthly_account_closures')) {
            $closuresPaid = (float)DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->where('paid_amount', '>', 0)
                ->get()
                ->filter(function ($c) use ($agentId) {
                    return !DB::table('payment_vouchers')
                        ->where('branch_agent_id', $agentId)
                        ->where(function ($q) use ($c) {
                            $q->where('extra_details->closure_id', $c->id)
                              ->orWhere(function ($q2) use ($c) {
                                  $q2->where('extra_details->year', $c->year)
                                     ->where('extra_details->month', $c->month);
                              });
                        })
                        ->exists();
                })
                ->sum('paid_amount');
        }

        return round($vouchersPaid + $transfersPaid + $closuresPaid, 2);
    }

    /**
     * Get all payment items for an agent tagged with month_key for monthly ledgers & account statements.
     *
     * @param int $agentId
     * @return array
     */
    public static function getAllPayments(int $agentId): array
    {
        $schema = DB::getSchemaBuilder();
        $allPayments = [];

        // 1. Payment Vouchers
        if ($schema->hasTable('payment_vouchers')) {
            $vouchers = DB::table('payment_vouchers')
                ->where('branch_agent_id', $agentId)
                ->get();

            foreach ($vouchers as $v) {
                $extra = is_string($v->extra_details) ? json_decode($v->extra_details, true) : (array)($v->extra_details ?? []);
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

        // 2. Approved Transfers without Payment Voucher
        if ($schema->hasTable('agent_transfers')) {
            $transfers = DB::table('agent_transfers')
                ->where('branch_agent_id', $agentId)
                ->where('status', 'approved')
                ->whereNull('payment_voucher_id')
                ->get();

            foreach ($transfers as $t) {
                $date = $t->transfer_date ?? $t->approval_date ?? $t->created_at ?? date('Y-m-d');
                $mKey = \Carbon\Carbon::parse($date)->format('Y-m');

                $allPayments[] = [
                    'amount'       => (float)$t->amount,
                    'payment_date' => $date,
                    'month_key'    => $mKey,
                    'source'       => 'agent_transfer',
                    'id'           => $t->id,
                ];
            }
        }

        // 3. Closures with paid_amount without Payment Voucher
        if ($schema->hasTable('monthly_account_closures')) {
            $closures = DB::table('monthly_account_closures')
                ->where('branch_agent_id', $agentId)
                ->where('paid_amount', '>', 0)
                ->get();

            foreach ($closures as $c) {
                $mKey = sprintf('%04d-%02d', (int)$c->year, (int)$c->month);
                $hasVoucher = DB::table('payment_vouchers')
                    ->where('branch_agent_id', $agentId)
                    ->where(function ($q) use ($c) {
                        $q->where('extra_details->closure_id', $c->id)
                          ->orWhere(function ($q2) use ($c) {
                              $q2->where('extra_details->year', $c->year)
                                 ->where('extra_details->month', $c->month);
                          });
                    })
                    ->exists();

                if (!$hasVoucher) {
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
