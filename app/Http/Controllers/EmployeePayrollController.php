<?php

namespace App\Http\Controllers;

use App\Models\EmployeePayroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'status' => 'nullable|in:paid,unpaid',
            'search' => 'nullable|string|max:150',
        ]);

        $query = EmployeePayroll::with(['user:id,name,username,email,salary', 'processor:id,name']);

        if (!empty($validated['year'])) {
            $query->where('year', $validated['year']);
        }
        if (!empty($validated['month'])) {
            $query->where('month', $validated['month']);
        }
        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        return response()->json($query->orderByDesc('year')->orderByDesc('month')->orderBy('id')->get());
    }

    public function upsert(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'base_salary' => 'nullable|numeric|min:0',
            'housing_allowance' => 'nullable|numeric',
            'transportation_allowance' => 'nullable|numeric',
            'communication_allowance' => 'nullable|numeric',
            'allowance_amount' => 'nullable|numeric',
            'bonus_amount' => 'nullable|numeric',
            'deduction_amount' => 'nullable|numeric',
            'advance_amount' => 'nullable|numeric',
            'penalty_amount' => 'nullable|numeric',
            'other_additions' => 'nullable|numeric',
            'status' => 'required|in:paid,unpaid',
            'delivery_method' => 'nullable|string',
            'custom_delivery_method' => 'nullable|string',
            'extra_fields' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $base = (float) ($validated['base_salary'] ?? 0);
        $housing = (float) ($validated['housing_allowance'] ?? 0);
        $transport = (float) ($validated['transportation_allowance'] ?? 0);
        $communication = (float) ($validated['communication_allowance'] ?? 0);
        $bonus = (float) ($validated['bonus_amount'] ?? 0);
        $other_additions = (float) ($validated['other_additions'] ?? 0);
        $deduction = (float) ($validated['deduction_amount'] ?? 0);
        $advance = (float) ($validated['advance_amount'] ?? 0);
        $penalty = (float) ($validated['penalty_amount'] ?? 0);
        
        // allowance_amount can be used for any additional miscellaneous allowances if needed
        $misc_allowance = (float) ($validated['allowance_amount'] ?? 0);

        $extra_total = 0;
        if (!empty($validated['extra_fields'])) {
            foreach ($validated['extra_fields'] as $field) {
                $extra_total += (float) ($field['amount'] ?? 0);
            }
        }

        $user = User::findOrFail($validated['user_id']);
        $tax_pct = (float) ($user->tax_percentage ?? 10.0);
        $ss_pct = (float) ($user->social_security_percentage ?? 19.475);

        $tax_amount = ($base * $tax_pct) / 100;
        $social_security_amount = ($base * $ss_pct) / 100;

        $net = $base + $housing + $transport + $communication + $bonus + $other_additions + $misc_allowance + $extra_total - $deduction - $advance - $penalty - $tax_amount - $social_security_amount;

        $payroll = EmployeePayroll::updateOrCreate(
            [
                'user_id' => $validated['user_id'],
                'year' => $validated['year'],
                'month' => $validated['month'],
            ],
            [
                'base_salary' => $base,
                'housing_allowance' => $housing,
                'transportation_allowance' => $transport,
                'communication_allowance' => $communication,
                'allowance_amount' => $misc_allowance,
                'bonus_amount' => $bonus,
                'deduction_amount' => $deduction,
                'advance_amount' => $advance,
                'penalty_amount' => $penalty,
                'tax_amount' => $tax_amount,
                'social_security_amount' => $social_security_amount,
                'other_additions' => $other_additions,
                'net_salary' => $net,
                'status' => $validated['status'],
                'delivery_method' => $validated['delivery_method'] ?? 'كاش',
                'custom_delivery_method' => $validated['custom_delivery_method'] ?? null,
                'extra_fields' => $validated['extra_fields'] ?? [],
                'paid_at' => $validated['status'] === 'paid' ? now() : null,
                'notes' => $validated['notes'] ?? null,
                'processed_by' => auth()->id(),
            ]
        );

        return response()->json($payroll->fresh(['user:id,name,username,email,salary', 'processor:id,name']));
    }

    public function employees()
    {
        $employees = User::with('branchAgent:id,user_id')
            ->select('id', 'name', 'username', 'email', 'salary', 'is_admin', 'tax_percentage', 'social_security_percentage')
            ->get()
            ->filter(function ($u) {
                return !$u->branchAgent;
            })
            ->values();

        return response()->json($employees);
    }

    /**
     * Mark payroll as paid for all employees (non–branch-agent) for a given month.
     * Creates payroll rows from user salary when none exist yet.
     */
    public function bulkPay(Request $request)
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $year = $validated['year'];
        $month = $validated['month'];

        $employees = User::with('branchAgent:id,user_id')
            ->select('id', 'name', 'username', 'email', 'salary', 'is_admin', 'tax_percentage', 'social_security_percentage')
            ->get()
            ->filter(function ($u) {
                return !$u->branchAgent;
            })
            ->values();

        $count = 0;

        DB::transaction(function () use ($employees, $year, $month, &$count) {
            foreach ($employees as $user) {
                $existing = EmployeePayroll::where('user_id', $user->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->first();

                $base = $existing ? (float) $existing->base_salary : (float) ($user->salary ?? 0);
                $housing = $existing ? (float) $existing->housing_allowance : 100.0;
                $transport = $existing ? (float) $existing->transportation_allowance : 100.0;
                $communication = $existing ? (float) $existing->communication_allowance : 100.0;
                $misc_allowance = $existing ? (float) $existing->allowance_amount : 0.0;
                $bonus = $existing ? (float) $existing->bonus_amount : 100.0;
                $other_additions = $existing ? (float) $existing->other_additions : 0.0;
                $deduction = $existing ? (float) $existing->deduction_amount : 75.0;
                $advance = $existing ? (float) $existing->advance_amount : 0.0;
                $penalty = $existing ? (float) $existing->penalty_amount : 0.0;
                $extra_fields = $existing ? ($existing->extra_fields ?? []) : [];
                $extra_total = 0;
                foreach ($extra_fields as $field) {
                    $extra_total += (float) ($field['amount'] ?? 0);
                }
                
                $tax_pct = (float) ($user->tax_percentage ?? 10.0);
                $ss_pct = (float) ($user->social_security_percentage ?? 19.475);

                $tax_amount = ($base * $tax_pct) / 100;
                $social_security_amount = ($base * $ss_pct) / 100;

                $net = $base + $housing + $transport + $communication + $bonus + $other_additions + $misc_allowance + $extra_total - $deduction - $advance - $penalty - $tax_amount - $social_security_amount;

                EmployeePayroll::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $year,
                        'month' => $month,
                    ],
                    [
                        'base_salary' => $base,
                        'housing_allowance' => $housing,
                        'transportation_allowance' => $transport,
                        'communication_allowance' => $communication,
                        'allowance_amount' => $misc_allowance,
                        'bonus_amount' => $bonus,
                        'deduction_amount' => $deduction,
                        'advance_amount' => $advance,
                        'penalty_amount' => $penalty,
                        'tax_amount' => $tax_amount,
                        'social_security_amount' => $social_security_amount,
                        'other_additions' => $other_additions,
                        'net_salary' => $net,
                        'status' => 'paid',
                        'delivery_method' => $existing ? $existing->delivery_method : 'كاش',
                        'custom_delivery_method' => $existing ? $existing->custom_delivery_method : null,
                        'extra_fields' => $extra_fields,
                        'paid_at' => now(),
                        'notes' => $existing?->notes,
                        'processed_by' => auth()->id(),
                    ]
                );
                $count++;
            }
        });

        return response()->json([
            'message' => 'تم تسجيل صرف المرتبات لجميع الموظفين لهذا الشهر',
            'count' => $count,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function taxSSReport(Request $request)
    {
        $validated = $request->validate([
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
        ]);

        $query = EmployeePayroll::with(['user:id,name,job_title,national_id_number,nationality,start_date,tax_percentage,social_security_percentage']);

        if (!empty($validated['year'])) {
            $query->where('year', $validated['year']);
        }
        if (!empty($validated['month'])) {
            $query->where('month', $validated['month']);
        }
        if (!empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        // Only include non-branch agents (already handled by Payroll table logic as only they have payrolls)
        return response()->json($query->orderByDesc('year')->orderByDesc('month')->get());
    }
}
