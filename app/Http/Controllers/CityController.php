<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $cities = City::orderBy('order', 'asc')->orderBy('name_ar', 'asc')->get();
            return response()->json($cities);
        } catch (\Exception $e) {
            Log::error('Error in CityController@index: ' . $e->getMessage());
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
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $city = City::create($validated);
            return response()->json($city, 201);
        } catch (\Exception $e) {
            Log::error('Error in CityController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء المدينة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $city = City::findOrFail($id);
            return response()->json($city);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'المدينة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in CityController@show: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validated = $request->validate([
                'name_ar' => 'required|string|max:255',
                'name_en' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $city = City::findOrFail($id);
            $city->update($validated);
            return response()->json($city);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'المدينة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in CityController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث المدينة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $city = City::findOrFail($id);
            $city->delete();
            return response()->json(['message' => 'تم حذف المدينة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'المدينة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in CityController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف المدينة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
