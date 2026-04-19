<?php

namespace App\Http\Controllers;

use App\Models\BankTransaction;
use Illuminate\Http\Request;

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
            'notes' => 'nullable|string'
        ]);

        $transaction = BankTransaction::create($validated);
        return response()->json($transaction, 201);
    }

    public function update(Request $request, $id)
    {
        $transaction = BankTransaction::findOrFail($id);
        $transaction->update($request->all());
        return response()->json($transaction);
    }

    public function destroy($id)
    {
        BankTransaction::destroy($id);
        return response()->json(['message' => 'Transaction deleted']);
    }

    public function toggleReconcile($id)
    {
        $transaction = BankTransaction::findOrFail($id);
        $transaction->update(['reconciled' => !$transaction->reconciled]);
        return response()->json($transaction);
    }
}
