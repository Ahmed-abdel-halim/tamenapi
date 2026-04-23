<?php

namespace App\Http\Controllers;

use App\Models\EmployeeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeRequest::with(['user', 'approver']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // If not admin, only show own requests
        if (!$request->user()->is_admin) {
            $query->where('user_id', $request->user()->id);
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:termination,leave_hourly,leave_daily,salary_advance,allowance,complaint,maintenance,other',
            'reason' => 'required|string',
            'with_salary' => 'boolean',
            'details' => 'nullable|array',
        ]);

        $employeeRequest = EmployeeRequest::create([
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'status' => 'pending',
            'reason' => $validated['reason'],
            'with_salary' => $validated['with_salary'] ?? true,
            'details' => $validated['details'] ?? [],
        ]);

        return response()->json($employeeRequest, 201);
    }

    public function show(EmployeeRequest $employeeRequest)
    {
        // Check authorization
        if (!Auth::user()->is_admin && $employeeRequest->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $employeeRequest->load(['user', 'approver']);
    }

    public function update(Request $request, EmployeeRequest $employeeRequest)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Only admins can process requests'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $employeeRequest->update([
            'status' => $validated['status'],
            'admin_notes' => $request->get('admin_notes'),
            'approver_id' => Auth::id(),
            'processed_at' => now(),
        ]);

        return $employeeRequest;
    }

    public function destroy(EmployeeRequest $employeeRequest)
    {
        if ($employeeRequest->user_id !== Auth::id() || $employeeRequest->status !== 'pending') {
            return response()->json(['message' => 'Cannot delete this request'], 403);
        }

        $employeeRequest->delete();
        return response()->json(null, 204);
    }
}
