<?php

namespace App\Http\Controllers;

use App\Models\BranchAgent;
use App\Models\AgentWalletTransaction;
use App\Models\AgentWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentWalletController extends Controller
{
    /**
     * Get agent wallet details.
     */
    public function getWalletDetails($id)
    {
        $agent = BranchAgent::findOrFail($id);

        // Generate referral code if not exists
        if (empty($agent->referral_code)) {
            $agent->referral_code = 'AG-' . $agent->id . '-' . strtoupper(Str::random(5));
            $agent->save();
        }

        $referralsCount = BranchAgent::where('referred_by_id', $agent->id)->count();

        // Calculate total referral cash bonus earned
        $totalReferralCash = AgentWalletTransaction::where('branch_agent_id', $agent->id)
            ->where('transaction_type', 'cash')
            ->where('action', 'referral_bonus')
            ->sum('amount');

        return response()->json([
            'points_balance' => (int)$agent->points_balance,
            'wallet_balance' => (float)$agent->wallet_balance,
            'referral_code' => $agent->referral_code,
            'referred_by_id' => $agent->referred_by_id,
            'referrals_count' => $referralsCount,
            'total_earned_referral_cash' => (float)$totalReferralCash,
        ]);
    }

    /**
     * Get wallet transactions ledger.
     */
    public function getTransactions(Request $request, $id)
    {
        $request->validate([
            'type' => 'nullable|string|in:points,cash,all',
        ]);

        $query = AgentWalletTransaction::where('branch_agent_id', $id);

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        return response()->json($transactions);
    }

    /**
     * Get withdrawals list.
     */
    public function getWithdrawals($id)
    {
        $withdrawals = AgentWithdrawal::where('branch_agent_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($withdrawals);
    }

    /**
     * Redeem loyalty points to cash balance.
     */
    public function redeemPoints(Request $request)
    {
        $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'points_to_redeem' => 'required|integer|min:1000',
        ]);

        $agent = BranchAgent::findOrFail($request->branch_agent_id);
        $points = (int)$request->points_to_redeem;

        if ($agent->points_balance < $points) {
            return response()->json([
                'message' => 'رصيد نقاط الولاء الحالي غير كافٍ لإجراء هذه العملية'
            ], 422);
        }

        // Standard conversion rate: 1000 points = 10.00 LYD
        $cashValue = ($points / 1000) * 10.00;

        DB::transaction(function () use ($agent, $points, $cashValue) {
            // Deduct points and add cash
            $agent->points_balance -= $points;
            $agent->wallet_balance += $cashValue;
            $agent->save();

            // Log points deduction
            AgentWalletTransaction::create([
                'branch_agent_id' => $agent->id,
                'transaction_type' => 'points',
                'amount' => -$points,
                'action' => 'redeem_points',
                'description' => "تحويل {$points} نقطة إلى رصيد مالي بقيمة {$cashValue} دينار",
            ]);

            // Log cash addition
            AgentWalletTransaction::create([
                'branch_agent_id' => $agent->id,
                'transaction_type' => 'cash',
                'amount' => $cashValue,
                'action' => 'redeem_points',
                'description' => "تحويل نقاط إلى رصيد مالي: كسب {$cashValue} دينار",
            ]);
        });

        return response()->json([
            'message' => 'تم استبدال النقاط بنجاح وتحويل القيمة إلى محفظتك المادية',
            'points_balance' => (int)$agent->points_balance,
            'wallet_balance' => (float)$agent->wallet_balance,
        ]);
    }

    /**
     * Submit withdrawal request.
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|max:255',
        ]);

        $agent = BranchAgent::findOrFail($request->branch_agent_id);
        $amount = (float)$request->amount;

        if ($agent->wallet_balance < $amount) {
            return response()->json([
                'message' => 'رصيد المحفظة الحالي غير كافٍ لطلب سحب هذا المبلغ'
            ], 422);
        }

        $withdrawal = DB::transaction(function () use ($agent, $amount, $request) {
            // Reserved immediately
            $agent->wallet_balance -= $amount;
            $agent->save();

            // Create withdrawal request
            $withdrawal = AgentWithdrawal::create([
                'branch_agent_id' => $agent->id,
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => $request->payment_method,
            ]);

            // Log reservation transaction
            AgentWalletTransaction::create([
                'branch_agent_id' => $agent->id,
                'transaction_type' => 'cash',
                'amount' => -$amount,
                'action' => 'withdraw_request',
                'description' => "طلب سحب رصيد معلق بقيمة {$amount} دينار عبر ({$request->payment_method})",
            ]);

            return $withdrawal;
        });

        // إرسال إشعار للمشرفين
        try {
            $admins = \App\Models\User::where('is_admin', true)->get();
            $agentName = $agent->agency_name ?? 'الوكيل';
            $title = 'طلب سحب جديد من محفظة وكيل';
            $message = "طلب سحب رصيد جديد بقيمة {$amount} د.ل من الوكيل ({$agentName}) عبر طريقة الدفع: {$withdrawal->payment_method}.";
            $url = "/branches-agents/{$agent->id}?tab=wallet";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'info', $url));
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in AgentWallet requestWithdrawal: ' . $ne->getMessage());
        }

        return response()->json([
            'message' => 'تم تقديم طلب السحب بنجاح وهو قيد المراجعة حالياً من قبل الإدارة',
            'withdrawal' => $withdrawal,
            'wallet_balance' => (float)$agent->wallet_balance,
        ]);
    }

    /**
     * Admin: Approve or reject withdrawal request.
     */
    public function updateWithdrawalStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $withdrawal = AgentWithdrawal::findOrFail($id);
        $agent = BranchAgent::findOrFail($withdrawal->branch_agent_id);

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'message' => 'تمت معالجة هذا الطلب مسبقاً ولا يمكن تعديل حالته'
            ], 422);
        }

        DB::transaction(function () use ($withdrawal, $agent, $request) {
            $withdrawal->status = $request->status;
            $withdrawal->admin_notes = $request->admin_notes;
            $withdrawal->save();

            if ($request->status === 'rejected') {
                // Return amount back to active balance
                $agent->wallet_balance += $withdrawal->amount;
                $agent->save();

                // Log refund transaction
                AgentWalletTransaction::create([
                    'branch_agent_id' => $agent->id,
                    'transaction_type' => 'cash',
                    'amount' => $withdrawal->amount,
                    'action' => 'withdraw_refund',
                    'description' => "استرجاع رصيد لطلب سحب مرفوض بقيمة {$withdrawal->amount} دينار",
                ]);
            } else {
                // Log final approval
                AgentWalletTransaction::create([
                    'branch_agent_id' => $agent->id,
                    'transaction_type' => 'cash',
                    'amount' => 0, // 0 delta, just a log entry
                    'action' => 'withdraw_approved',
                    'description' => "تمت الموافقة من الإدارة على طلب سحب رصيد بقيمة {$withdrawal->amount} دينار",
                ]);
            }
        });

        // إرسال إشعار للوكيل
        try {
            if ($agent->user_id) {
                $agentUser = \App\Models\User::find($agent->user_id);
                if ($agentUser) {
                    $statusNames = [
                        'approved' => 'تمت الموافقة والتسليم',
                        'rejected' => 'مرفوض وتم إرجاع الرصيد للمحفظة'
                    ];
                    $statusText = $statusNames[$request->status] ?? $request->status;
                    $title = 'تحديث حالة طلب السحب المالي';
                    $message = "تم تحديث طلب السحب المالي بقيمة {$withdrawal->amount} د.ل الخاص بك إلى: {$statusText}";
                    if ($request->filled('admin_notes')) {
                        $message .= " | ملاحظة الإدارة: {$request->admin_notes}";
                    }
                    $url = "/branches-agents/{$agent->id}?tab=wallet";
                    $agentUser->notify(new \App\Notifications\SystemNotification($title, $message, $request->status === 'rejected' ? 'error' : 'success', $url));
                }
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in AgentWallet updateWithdrawalStatus: ' . $ne->getMessage());
        }

        return response()->json([
            'message' => $request->status === 'approved' ? 'تمت الموافقة على طلب السحب بنجاح' : 'تم رفض طلب السحب وإرجاع المبلغ للمحفظة',
            'withdrawal' => $withdrawal,
            'wallet_balance' => (float)$agent->wallet_balance,
        ]);
    }

    /**
     * Admin: Manually adjust points or cash balance.
     */
    public function adjustWallet(Request $request)
    {
        $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'points_amount' => 'nullable|integer',
            'cash_amount' => 'nullable|numeric',
            'reason' => 'required|string|max:500',
        ]);

        $agent = BranchAgent::findOrFail($request->branch_agent_id);
        $pointsAdj = (int)($request->points_amount ?? 0);
        $cashAdj = (float)($request->cash_amount ?? 0);

        if ($pointsAdj === 0 && $cashAdj === 0) {
            return response()->json([
                'message' => 'يجب إدخال قيمة تعديل للنقاط أو الرصيد المالي'
            ], 422);
        }

        DB::transaction(function () use ($agent, $pointsAdj, $cashAdj, $request) {
            if ($pointsAdj !== 0) {
                $agent->points_balance += $pointsAdj;
                
                AgentWalletTransaction::create([
                    'branch_agent_id' => $agent->id,
                    'transaction_type' => 'points',
                    'amount' => $pointsAdj,
                    'action' => 'admin_adjustment',
                    'description' => "تعديل إداري للرصيد: " . ($pointsAdj > 0 ? "شحن" : "خصم") . " " . abs($pointsAdj) . " نقطة. (السبب: {$request->reason})",
                ]);
            }

            if ($cashAdj !== 0) {
                $agent->wallet_balance += $cashAdj;

                AgentWalletTransaction::create([
                    'branch_agent_id' => $agent->id,
                    'transaction_type' => 'cash',
                    'amount' => $cashAdj,
                    'action' => 'admin_adjustment',
                    'description' => "تعديل إداري للرصيد: " . ($cashAdj > 0 ? "إضافة" : "خصم") . " " . abs($cashAdj) . " دينار. (السبب: {$request->reason})",
                ]);
            }

            $agent->save();
        });

        return response()->json([
            'message' => 'تم تعديل أرصدة المحفظة يدوياً بنجاح وتسجيل العملية',
            'points_balance' => (int)$agent->points_balance,
            'wallet_balance' => (float)$agent->wallet_balance,
        ]);
    }

    /**
     * Get list of referred agents.
     */
    public function getReferrals($id)
    {
        $referrals = BranchAgent::where('referred_by_id', $id)
            ->select('id', 'agency_name', 'agent_name', 'code', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($referrals);
    }

    /**
     * Get loyalty settings for all policy types.
     */
    public function getLoyaltySettings()
    {
        $settings = \App\Models\AgentLoyaltySetting::orderBy('id', 'asc')->get();
        return response()->json($settings);
    }

    /**
     * Save loyalty settings.
     */
    public function saveLoyaltySettings(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
            'settings.*.policy_type' => 'required|string|exists:agent_loyalty_settings,policy_type',
            'settings.*.points_reward' => 'required|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            foreach ($request->settings as $settingData) {
                \App\Models\AgentLoyaltySetting::where('policy_type', $settingData['policy_type'])
                    ->update(['points_reward' => (int)$settingData['points_reward']]);
            }
        });

        return response()->json([
            'message' => 'تم حفظ إعدادات نقاط الولاء بنجاح'
        ]);
    }
}
