<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Commission::with('agent');

        if ($request->has('agent_id')) {
            $query->where('branch_agent_id', $request->agent_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'document_type' => 'required|string',
            'document_number' => 'required|string',
            'total_amount' => 'required|numeric',
            'commission_rate' => 'required|numeric',
            'commission_amount' => 'required|numeric',
            'status' => 'required|in:pending,paid',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        $commission = Commission::create($validated);
        return response()->json($commission, 201);
    }

    public function update(Request $request, $id)
    {
        $commission = Commission::findOrFail($id);
        $commission->update($request->all());
        return response()->json($commission);
    }

    public function destroy($id)
    {
        Commission::destroy($id);
        return response()->json(['message' => 'Commission deleted']);
    }

    public function markAsPaid($id)
    {
        $commission = Commission::findOrFail($id);
        $commission->update([
            'status' => 'paid',
            'payment_date' => now()
        ]);
        return response()->json($commission);
    }
}
