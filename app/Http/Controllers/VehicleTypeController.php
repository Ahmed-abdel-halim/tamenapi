<?php

namespace App\Http\Controllers;

use App\Models\VehicleType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $vehicleTypes = VehicleType::orderBy('brand', 'asc')->orderBy('category', 'asc')->get();
            return response()->json($vehicleTypes);
        } catch (\Exception $e) {
            Log::error('Error in VehicleTypeController@index: ' . $e->getMessage());
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
                'brand' => 'required|string|max:255',
                'category' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $vehicleType = VehicleType::create($validated);
            return response()->json($vehicleType, 201);
        } catch (\Exception $e) {
            Log::error('Error in VehicleTypeController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء نوع المركبة',
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
            $vehicleType = VehicleType::findOrFail($id);
            return response()->json($vehicleType);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'نوع المركبة غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in VehicleTypeController@show: ' . $e->getMessage());
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
                'brand' => 'required|string|max:255',
                'category' => 'required|string|max:255',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $vehicleType = VehicleType::findOrFail($id);
            $vehicleType->update($validated);
            return response()->json($vehicleType);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'نوع المركبة غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in VehicleTypeController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث نوع المركبة',
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
            $vehicleType = VehicleType::findOrFail($id);
            $vehicleType->delete();
            return response()->json(['message' => 'تم حذف نوع المركبة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'نوع المركبة غير موجود'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in VehicleTypeController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف نوع المركبة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
