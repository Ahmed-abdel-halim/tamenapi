<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $categories = ExpenseCategory::orderBy('name')->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
        ]);

        $category = ExpenseCategory::create($validated);

        return response()->json([
            'success' => true,
            'data' => $category,
            'message' => 'تم إضافة الفئة بنجاح'
        ], 201);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
        ]);

        $expenseCategory->update($validated);

        return response()->json([
            'success' => true,
            'data' => $expenseCategory,
            'message' => 'تم تعديل الفئة بنجاح'
        ]);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        // Prevent deleting 'التعويضات' as it's a core system category
        if ($expenseCategory->name === 'التعويضات') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف هذه الفئة الأساسية'
            ], 403);
        }

        $expenseCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الفئة بنجاح'
        ]);
    }
}
