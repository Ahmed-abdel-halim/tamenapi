<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Branch;
use App\Models\BranchAgent;

trait HandlesBranchScope
{
    /**
     * Resolve the active branch ID from authenticated user, header, or query.
     *
     * Rules:
     * 1. If user is a Branch Admin or branch user (not Super Admin), they are strictly scoped to their assigned branch_id.
     * 2. If user is a Super Admin (or no user session yet):
     *    - Read 'X-Branch-Id' header (set by frontend BranchSwitcher).
     *    - Read 'branch_id' query/post parameter.
     *    - If neither is provided or value is 'all', return null (meaning all branches).
     */
    protected function getResolvedBranchId(Request $request): ?int
    {
        $user = $request->user();
        if (!$user && $request->header('X-User-Id')) {
            $user = User::find($request->header('X-User-Id'));
        }

        // Branch Admin or branch user (not Super Admin) is strictly locked to their branch
        if ($user && !(bool)($user->is_admin ?? false)) {
            if (!empty($user->branch_id)) {
                return (int) $user->branch_id;
            }
            if ($user->managedBranch) {
                return (int) $user->managedBranch->id;
            }
        }

        // Header passed by frontend BranchSwitcher
        $headerBranch = $request->header('X-Branch-Id');
        if ($headerBranch && $headerBranch !== 'all' && is_numeric($headerBranch)) {
            return (int) $headerBranch;
        }

        // Query param
        $inputBranch = $request->input('branch_id');
        if ($inputBranch && $inputBranch !== 'all' && is_numeric($inputBranch)) {
            return (int) $inputBranch;
        }

        return null;
    }

    /**
     * Determine if a branch is the main branch (or HQ).
     */
    protected function isMainBranch(?int $branchId): bool
    {
        if (!$branchId) {
            return false;
        }
        return (int)$branchId === 1 || Branch::where('id', $branchId)->where('is_main_branch', true)->exists();
    }

    /**
     * Get all agent IDs affiliated with a branch.
     * For the main branch, includes both agents explicitly assigned to $branchId and legacy unassigned agents (branch_id IS NULL).
     */
    protected function getBranchAgentIds(?int $branchId): array
    {
        if (!$branchId) {
            return [];
        }
        $query = BranchAgent::query();
        if ($this->isMainBranch($branchId)) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)
                  ->orWhereNull('branch_id');
            });
        } else {
            $query->where('branch_id', $branchId);
        }
        return $query->pluck('id')->toArray();
    }

    /**
     * Apply branch scope to a Query Builder or Eloquent query.
     */
    protected function applyBranchScope($query, Request $request, string $column = 'branch_id')
    {
        $branchId = $this->getResolvedBranchId($request);
        if ($branchId) {
            if ($this->isMainBranch($branchId)) {
                $query->where(function ($q) use ($branchId, $column) {
                    $q->where($column, $branchId)
                      ->orWhereNull($column);
                });
            } else {
                $query->where($column, $branchId);
            }
        }
        return $query;
    }
}
