<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BankTransactionController extends Controller
{
    public function index()
    {
        return response()->json(BankTransaction::orderBy('transaction_date', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'bank_name' => 'required|string',
            'account_number' => 'nullable|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:deposit,withdrawal',
            'notes' => 'nullable|string',
            'transaction_type' => 'nullable|string',
            'source_bank' => 'nullable|string',
            'destination_bank' => 'nullable|string',
            'branch_agent_id' => 'nullable|integer',
            'payer_name' => 'nullable|string',
            'payer_phone' => 'nullable|string',
            'voucher_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240',
        ]);

        $data = $request->except('voucher_image');

        if ($request->filled('branch_agent_id')) {
            $agent = \App\Models\BranchAgent::find($request->branch_agent_id);
            if ($agent) {
                $data['agent_name'] = $agent->agency_name;
            }
        }

        if ($request->hasFile('voucher_image')) {
            $path = $request->file('voucher_image')->store('bank_vouchers', 'public');
            $data['voucher_image'] = $path;
        }

        $transaction = BankTransaction::create($data);
        return response()->json($transaction, 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = BankTransaction::findOrFail($id);
        
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'bank_name' => 'required|string',
            'account_number' => 'nullable|string',
            'amount' => 'required|numeric',
            'type' => 'required|in:deposit,withdrawal',
            'notes' => 'nullable|string',
            'transaction_type' => 'nullable|string',
            'source_bank' => 'nullable|string',
            'destination_bank' => 'nullable|string',
            'branch_agent_id' => 'nullable|integer',
            'payer_name' => 'nullable|string',
            'payer_phone' => 'nullable|string',
            'voucher_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240',
        ]);

        $data = $request->except('voucher_image');

        if ($request->filled('branch_agent_id')) {
            $agent = \App\Models\BranchAgent::find($request->branch_agent_id);
            if ($agent) {
                $data['agent_name'] = $agent->agency_name;
            }
        }

        if ($request->hasFile('voucher_image')) {
            // Delete old file
            if ($transaction->voucher_image) {
                Storage::disk('public')->delete($transaction->voucher_image);
            }
            $path = $request->file('voucher_image')->store('bank_vouchers', 'public');
            $data['voucher_image'] = $path;
        }

        $transaction->update($data);
        return response()->json($transaction);
    }

    public function destroy($id)
    {
        $transaction = BankTransaction::findOrFail($id);
        if ($transaction->voucher_image) {
            Storage::disk('public')->delete($transaction->voucher_image);
        }
        $transaction->delete();
        return response()->json(['message' => 'Transaction deleted']);
    }

    public function toggleReconcile($id)
    {
        $transaction = BankTransaction::findOrFail($id);
        $transaction->update(['reconciled' => !$transaction->reconciled]);
        return response()->json($transaction);
    }
}
