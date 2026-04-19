<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    /**
     * Get all expenses.
     */
    public function index(Request $request)
    {
        $expenses = Expense::orderBy('expense_date', 'desc')
            ->orderBy('created_at', 'desc');

        if ($request->has('category')) {
            $expenses->where('category', $request->category);
        }

        if ($request->has('status')) {
            $expenses->where('status', $request->status);
        }

        // إحصائيات الشهر الحالي
        $now = now();
        $monthlyTotal = Expense::whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)
            ->sum('amount');
        
        $monthlyCount = Expense::whereMonth('expense_date', $now->month)
            ->whereYear('expense_date', $now->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => $expenses->get(),
            'statistics' => [
                'monthly_total' => (float)$monthlyTotal,
                'monthly_count' => $monthlyCount,
                'monthly_average' => $monthlyCount > 0 ? (float)($monthlyTotal / $monthlyCount) : 0,
            ]
        ]);
    }

    /**
     * Store a new expense.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_indemnity' => 'nullable|boolean',
            'indemnity_type' => 'nullable|string',
            'payment_source' => 'nullable|string',
        ]);

        try {
            $data = $request->all();

            // للتعامل مع القيود في حال لم يتم تشغيل الهجرة (Migration)
            // نحاول جلب أول تصنيف وأول خزينة مسجلة
            if (DB::getSchemaBuilder()->hasColumn('expenses', 'expense_category_id')) {
                $data['expense_category_id'] = DB::table('expense_categories')->first()->id ?? null;
            }
            if (DB::getSchemaBuilder()->hasColumn('expenses', 'treasury_id')) {
                $data['treasury_id'] = DB::table('treasuries')->first()->id ?? null;
            }

            $expense = Expense::create($data);
            return response()->json([
                'success' => true,
                'data' => $expense
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an expense.
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
            'is_indemnity' => 'nullable|boolean',
            'indemnity_type' => 'nullable|string',
            'payment_source' => 'nullable|string',
        ]);

        try {
            $expense->update($request->all());
            return response()->json([
                'success' => true,
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an expense.
     */
    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();
        return response()->json([
            'success' => true,
            'message' => 'تم حذف المصروف بنجاح'
        ]);
    }
}
