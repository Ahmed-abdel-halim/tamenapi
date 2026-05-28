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

        // إرسال إشعار للمشرفين
        try {
            $admins = \App\Models\User::where('is_admin', true)->get();
            $employeeName = $employeeRequest->user?->name ?? 'الموظف';
            $typeNames = [
                'termination' => 'إنهاء خدمة',
                'leave_hourly' => 'مغادرة ساعية',
                'leave_daily' => 'إجازة يومية',
                'salary_advance' => 'سلفة على المرتب',
                'allowance' => 'طلب علاوة',
                'complaint' => 'شكوى',
                'maintenance' => 'طلب صيانة',
                'other' => 'أخرى'
            ];
            $typeName = $typeNames[$employeeRequest->type] ?? $employeeRequest->type;
            $title = 'طلب جديد من موظف';
            $message = "طلب جديد ({$typeName}) من الموظف: {$employeeName}";
            $url = "/employee-requests";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'info', $url));
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in EmployeeRequest store: ' . $ne->getMessage());
        }

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

        // إرسال إشعار للموظف
        try {
            if ($employeeRequest->user_id) {
                $employeeUser = \App\Models\User::find($employeeRequest->user_id);
                if ($employeeUser) {
                    $typeNames = [
                        'termination' => 'إنهاء خدمة',
                        'leave_hourly' => 'مغادرة ساعية',
                        'leave_daily' => 'إجازة يومية',
                        'salary_advance' => 'سلفة على المرتب',
                        'allowance' => 'طلب علاوة',
                        'complaint' => 'شكوى',
                        'maintenance' => 'طلب صيانة',
                        'other' => 'أخرى'
                    ];
                    $typeName = $typeNames[$employeeRequest->type] ?? $employeeRequest->type;
                    $statusNames = [
                        'approved' => 'مقبول (تمت الموافقة)',
                        'rejected' => 'مرفوض'
                    ];
                    $statusText = $statusNames[$validated['status']] ?? $validated['status'];
                    $title = 'تحديث حالة طلبك الشخصي';
                    $message = "تم تحديث طلبك المعنون بـ ({$typeName}) إلى: {$statusText}";
                    $url = "/users/{$employeeRequest->user_id}?tab=requests";
                    $employeeUser->notify(new \App\Notifications\SystemNotification($title, $message, $validated['status'] === 'rejected' ? 'error' : 'success', $url));
                }
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in EmployeeRequest update: ' . $ne->getMessage());
        }

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
