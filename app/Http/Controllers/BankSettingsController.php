<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransactionType;
use App\Models\SourceBank;
use Illuminate\Http\Request;

class BankSettingsController extends Controller
{
    public function getBanks()
    {
        return response()->json(Bank::all());
    }

    public function storeBank(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:banks,name',
            'account_number' => 'nullable|string'
        ]);
        $bank = Bank::create([
            'name' => $request->name,
            'account_number' => $request->account_number
        ]);
        return response()->json(['success' => true, 'data' => $bank]);
    }

    public function deleteBank($id)
    {
        Bank::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function updateBank(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:banks,name,' . $id,
            'account_number' => 'nullable|string'
        ]);
        $bank = Bank::findOrFail($id);
        $bank->update([
            'name' => $request->name,
            'account_number' => $request->account_number
        ]);
        return response()->json(['success' => true, 'data' => $bank]);
    }

    // Source Banks Management
    public function getSourceBanks()
    {
        return response()->json(SourceBank::all());
    }

    public function storeSourceBank(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:source_banks,name',
            'account_number' => 'nullable|string'
        ]);
        $bank = SourceBank::create([
            'name' => $request->name,
            'account_number' => $request->account_number
        ]);
        return response()->json(['success' => true, 'data' => $bank]);
    }

    public function deleteSourceBank($id)
    {
        SourceBank::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function updateSourceBank(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|unique:source_banks,name,' . $id,
            'account_number' => 'nullable|string'
        ]);
        $bank = SourceBank::findOrFail($id);
        $bank->update([
            'name' => $request->name,
            'account_number' => $request->account_number
        ]);
        return response()->json(['success' => true, 'data' => $bank]);
    }

    public function getTransactionTypes()
    {
        return response()->json(BankTransactionType::all());
    }

    public function storeTransactionType(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:bank_transaction_types,name']);
        $type = BankTransactionType::create(['name' => $request->name]);
        return response()->json(['success' => true, 'data' => $type]);
    }

    public function deleteTransactionType($id)
    {
        BankTransactionType::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
