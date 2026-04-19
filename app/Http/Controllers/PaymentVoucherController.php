<?php

namespace App\Http\Controllers;

use App\Models\PaymentVoucher;
use App\Models\BranchAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentVoucherController extends Controller
{
    /**
     * Get all payment vouchers.
     */
    public function index(Request $request)
    {
        $vouchers = PaymentVoucher::with('agent')
            ->orderBy('created_at', 'desc');

        if ($request->has('branch_agent_id')) {
            $vouchers->where('branch_agent_id', $request->branch_agent_id);
        }

        return response()->json($vouchers->get());
    }

    /**
     * Store a new payment voucher.
     */
    public function store(Request $request)
    {
        $request->validate([
            'voucher_number' => 'required|string|unique:payment_vouchers',
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'bank_name' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'extra_details' => 'nullable|array',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $voucher = PaymentVoucher::create($request->all());
            return response()->json($voucher, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a payment voucher.
     */
    public function update(Request $request, $id)
    {
        $voucher = PaymentVoucher::findOrFail($id);

        $request->validate([
            'voucher_number' => 'required|string|unique:payment_vouchers,voucher_number,' . $id,
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'bank_name' => 'nullable|string',
            'reference_number' => 'nullable|string',
            'extra_details' => 'nullable|array',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $voucher->update($request->all());
            return response()->json($voucher);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a payment voucher.
     */
    public function destroy($id)
    {
        $voucher = PaymentVoucher::findOrFail($id);
        $voucher->delete();
        return response()->json(['message' => 'Voucher deleted successfully']);
    }
}
