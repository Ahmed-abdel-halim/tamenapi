<?php

namespace App\Traits;

use Illuminate\Http\Request;
use App\Models\User;

trait HandlesBranchScope
{
    /**
     * Resolve the active branch ID from authenticated user, header, or query.
     *
     * Rules:
     * 1. If user is a Branch Admin (not Super Admin), they are strictly scoped to their assigned branch_id.
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
     * Apply branch scope to a Query Builder or Eloquent query.
     */
    protected function applyBranchScope($query, Request $request, string $column = 'branch_id')
    {
        $branchId = $this->getResolvedBranchId($request);
        if ($branchId) {
            $query->where($column, $branchId);
        }
        return $query;
    }
}
