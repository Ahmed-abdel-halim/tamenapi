<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProfessionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $professions = Profession::orderBy('name')->get();
            return response()->json($professions);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionController@index: ' . $e->getMessage());
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
                'name' => 'required|string|max:255|unique:professions,name',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $profession = Profession::create($validated);
            return response()->json($profession, 201);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء المهنة',
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
            $profession = Profession::findOrFail($id);
            $profession->delete();
            return response()->json(['message' => 'تم حذف المهنة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'المهنة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in ProfessionController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف المهنة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
