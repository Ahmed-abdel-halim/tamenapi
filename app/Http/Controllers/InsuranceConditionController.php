<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCondition;
use Illuminate\Http\Request;

class InsuranceConditionController extends Controller
{
    public function show($type)
    {
        $condition = InsuranceCondition::where('insurance_type', $type)->first();
        return response()->json([
            'insurance_type' => $type,
            'conditions' => $condition ? $condition->conditions : ''
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'insurance_type' => 'required|string',
            'conditions' => 'required|string',
        ]);

        $condition = InsuranceCondition::updateOrCreate(
            ['insurance_type' => $validated['insurance_type']],
            ['conditions' => $validated['conditions']]
        );

        return response()->json([
            'message' => 'تم حفظ الشروط بنجاح',
            'data' => $condition
        ]);
    }

    public function index()
    {
        return response()->json(InsuranceCondition::all()->pluck('conditions', 'insurance_type'));
    }
}