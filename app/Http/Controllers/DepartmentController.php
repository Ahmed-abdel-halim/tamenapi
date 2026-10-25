<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Traits\HandlesBranchScope;

class DepartmentController extends Controller
{
    use HandlesBranchScope;

    public function index(Request $request)
    {
        try {
            $query = Department::with(['users' => function ($q) {
                $q->select('id', 'name', 'job_title', 'personal_phone', 'gender', 'profile_photo_path', 'is_active', 'show_on_landing', 'department_id', 'branch_id');
            }, 'branch']);

            $this->applyBranchScope($query, $request);

            $departments = $query->get();

            return response()->json($departments);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error in DepartmentController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب قائمة الأقسام',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $branchId = $validated['branch_id'] ?? $this->getResolvedBranchId($request) ?? $request->user()?->branch_id ?? 1;

        return DB::transaction(function () use ($validated, $branchId) {
            $department = Department::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'branch_id' => $branchId,
            ]);

            if (!empty($validated['user_ids'])) {
                User::whereIn('id', $validated['user_ids'])->update(['department_id' => $department->id]);
            }

            return response()->json($department->load(['users', 'branch']), 201);
        });
    }

    public function show($id)
    {
        $department = Department::with(['users' => function ($query) {
            $query->where('is_active', true);
        }, 'branch'])->findOrFail($id);

        return response()->json($department);
    }

    public function update(Request $request, $id)
    {
        $department = Department::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        return DB::transaction(function () use ($validated, $department) {
            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ];
            if (isset($validated['branch_id'])) {
                $updateData['branch_id'] = $validated['branch_id'];
            }

            $department->update($updateData);

            // Reset previously assigned users
            User::where('department_id', $department->id)->update(['department_id' => null]);

            // Assign new users
            if (!empty($validated['user_ids'])) {
                User::whereIn('id', $validated['user_ids'])->update(['department_id' => $department->id]);
            }

            return response()->json($department->load(['users', 'branch']));
        });
    }

    public function destroy($id)
    {
        $department = Department::findOrFail($id);

        DB::transaction(function () use ($department) {
            User::where('department_id', $department->id)->update(['department_id' => null]);
            $department->delete();
        });

        return response()->json(['status' => 'deleted']);
    }

    /**
     * أقسام الواجهة العامة (Public Website Landing Page)
     * تظل عامة لكافة الأقسام والموظفين لعرض كوادر المنظومة للجمهور
     */
    public function publicDepartments()
    {
        try {
            $departments = Department::with(['users' => function ($query) {
                $query->where('is_active', true)
                    ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('start_date', 'asc');
            }])->get();

            return response()->json($departments);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الأقسام',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
