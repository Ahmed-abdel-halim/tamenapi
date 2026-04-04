<?php

namespace App\Http\Controllers;

use App\Models\MarineEngineModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarineEngineModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $models = MarineEngineModel::orderBy('name', 'asc')->pluck('name')->toArray();
            return response()->json($models);
        } catch (\Exception $e) {
            Log::error('Error in MarineEngineModelController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب أنواع المحركات',
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
                'name' => 'required|string|max:255|unique:marine_engine_models,name',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            $model = MarineEngineModel::create([
                'name' => $validated['name'],
            ]);

            return response()->json(['id' => $model->id, 'name' => $model->name], 201);
        } catch (\Exception $e) {
            Log::error('Error in MarineEngineModelController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء إضافة نوع المحرك',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }
}
