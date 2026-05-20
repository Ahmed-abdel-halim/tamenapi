<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\BankTransactionType;
use Illuminate\Http\Request;

class BankSettingsController extends Controller
{
    public function getBanks()
    {
        return response()->json(Bank::all());
    }

    public function storeBank(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:banks,name']);
        $bank = Bank::create(['name' => $request->name]);
        return response()->json(['success' => true, 'data' => $bank]);
    }

    public function deleteBank($id)
    {
        Bank::findOrFail($id)->delete();
        return response()->json(['success' => true]);
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
