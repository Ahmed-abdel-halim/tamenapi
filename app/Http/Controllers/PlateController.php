<?php

namespace App\Http\Controllers;

use App\Models\Plate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $plates = Plate::with('city:id,name_ar,name_en')
                ->orderBy('created_at', 'desc')
                ->get();
            return response()->json($plates);
        } catch (\Exception $e) {
            Log::error('Error in PlateController@index: ' . $e->getMessage());
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
                'plate_number' => 'required|string|max:255',
                'city_id' => 'required|exists:cities,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $plate = Plate::create($validated);
            return response()->json($plate->load('city'), 201);
        } catch (\Exception $e) {
            Log::error('Error in PlateController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إنشاء اللوحة',
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
            $plate = Plate::with('city')->findOrFail($id);
            return response()->json($plate);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'اللوحة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in PlateController@show: ' . $e->getMessage());
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
                'plate_number' => 'required|string|max:255',
                'city_id' => 'required|exists:cities,id',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $plate = Plate::findOrFail($id);
            $plate->update($validated);
            return response()->json($plate->load('city'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'اللوحة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in PlateController@update: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث اللوحة',
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
            $plate = Plate::findOrFail($id);
            $plate->delete();
            return response()->json(['message' => 'تم حذف اللوحة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'اللوحة غير موجودة'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error in PlateController@destroy: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء حذف اللوحة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
