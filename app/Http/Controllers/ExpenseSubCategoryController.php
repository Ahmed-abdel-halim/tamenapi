<?php

namespace App\Http\Controllers;

use App\Models\ExpenseSubCategory;
use Illuminate\Http\Request;

class ExpenseSubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ExpenseSubCategory::query();

        if ($request->has('category_name') && $request->category_name != '') {
            $query->where('category_name', $request->category_name);
        }

        $subcategories = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $subcategories
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
        ]);

        // Prevent duplicate subcategory under same category
        $exists = ExpenseSubCategory::where('category_name', $validated['category_name'])
                                    ->where('name', $validated['name'])
                                    ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'هذا البند الفرعي موجود بالفعل تحت هذه الفئة'
            ], 422);
        }

        $subcategory = ExpenseSubCategory::create($validated);

        return response()->json([
            'success' => true,
            'data' => $subcategory,
            'message' => 'تم إضافة البند الفرعي بنجاح'
        ], 201);
    }

    public function destroy($id)
    {
        $subcategory = ExpenseSubCategory::findOrFail($id);
        $subcategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف البند الفرعي بنجاح'
        ]);
    }
}
