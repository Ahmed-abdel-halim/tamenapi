<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ColorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $colors = Color::orderBy('name')->get();
            return response()->json($colors);
        } catch (\Exception $e) {
            Log::error('Error in ColorController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:colors,name',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $color = Color::create($validated);
            return response()->json($color, 201);
        } catch (\Exception $e) {
            Log::error('Error in ColorController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء اللون',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $color = Color::findOrFail($id);
            $color->delete();
            return response()->json(['message' => 'تم حذف اللون بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'اللون غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in ColorController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف اللون',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
