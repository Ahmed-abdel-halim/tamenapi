<?php

namespace App\Services;

use App\Models\BranchAgent;
use App\Models\AgentWalletTransaction;

class AgentRewardService
{
    /**
     * Reward points and referral commission for issuing a policy.
     */
    public static function rewardForPolicy($policy)
    {
        $agentId = $policy->branch_agent_id;
        if (!$agentId) {
            return;
        }

        $agent = BranchAgent::find($agentId);
        if (!$agent) {
            return;
        }

        // Determine points to reward based on model class name
        $className = class_basename($policy);
        $pointsReward = 10; // Default points

        try {
            $setting = \App\Models\AgentLoyaltySetting::where('policy_type', $className)->first();
            if ($setting) {
                $pointsReward = $setting->points_reward;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to fetch loyalty setting from DB, using default points: ' . $e->getMessage());
        }

        $policyNum = $policy->insurance_number ?? ('ID-' . $policy->id);
        $description = "كسب نقاط لإصدار وثيقة بقيمة {$pointsReward} نقطة (وثيقة رقم: {$policyNum})";

        // Reward points to the agent issuing the policy
        self::addPoints($agent, $pointsReward, 'earn_points', $description);

        // Referral reward check: If this agent was referred by another agent
        if ($agent->referred_by_id) {
            $referrer = BranchAgent::find($agent->referred_by_id);
            if ($referrer) {
                // Referral rewards: 20% of agent's points, plus a 1.00 LYD cash commission bonus!
                $referralPoints = (int)ceil($pointsReward * 0.20);
                $referralCash = 1.00;

                $refPointsDesc = "مكافأة إحالة (نقاط): وكيلك المسجل ({$agent->agency_name}) أصدر وثيقة رقم: {$policyNum}";
                self::addPoints($referrer, $referralPoints, 'referral_bonus', $refPointsDesc);

                $refCashDesc = "مكافأة إحالة (كاش): عمولة إحالة لتأدية وكيلك ({$agent->agency_name}) لوثيقة رقم: {$policyNum}";
                self::addCash($referrer, $referralCash, 'referral_bonus', $refCashDesc);
            }
        }
    }

    /**
     * Add points to agent balance and log transaction.
     */
    public static function addPoints(BranchAgent $agent, int $amount, string $action, string $description)
    {
        if ($amount === 0) return;
        
        $agent->points_balance += $amount;
        $agent->save();

        AgentWalletTransaction::create([
            'branch_agent_id' => $agent->id,
            'transaction_type' => 'points',
            'amount' => $amount,
            'action' => $action,
            'description' => $description,
        ]);
    }

    /**
     * Add cash to agent balance and log transaction.
     */
    public static function addCash(BranchAgent $agent, float $amount, string $action, string $description)
    {
        if ($amount === 0.0) return;

        $agent->wallet_balance += $amount;
        $agent->save();

        AgentWalletTransaction::create([
            'branch_agent_id' => $agent->id,
            'transaction_type' => 'cash',
            'amount' => $amount,
            'action' => $action,
            'description' => $description,
        ]);
    }
}
