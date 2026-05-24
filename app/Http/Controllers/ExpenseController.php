<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%")
                  ->orWhere('voucher_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        // Calculate statistics (for current month)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        $monthlyTotal = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $monthlyCount = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->count();

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'statistics' => [
                'monthly_total' => (float)$monthlyTotal,
                'monthly_count' => $monthlyCount,
            ]
        ]);
    }

    public function show($id)
    {
        $expense = Expense::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $expense
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('items') && is_string($request->items)) {
            $request->merge([
                'items' => json_decode($request->items, true)
            ]);
        }

        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'voucher_number' => 'nullable|string',
            'expense_type' => 'required|string',
            'expense_date' => 'required|date',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'is_indemnity' => 'nullable|boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('expense_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        $expense = Expense::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة المصروف بنجاح',
            'data' => $expense
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if ($request->has('items') && is_string($request->items)) {
            $request->merge([
                'items' => json_decode($request->items, true)
            ]);
        }

        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'voucher_number' => 'nullable|string',
            'expense_type' => 'required|string',
            'expense_date' => 'required|date',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'is_indemnity' => 'nullable|boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('receipt_image')) {
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            $path = $request->file('receipt_image')->store('expense_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        $expense->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المصروف بنجاح',
            'data' => $expense
        ]);
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        
        if ($expense->receipt_image) {
            Storage::disk('public')->delete($expense->receipt_image);
        }
        
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المصروف بنجاح'
        ]);
    }
}
