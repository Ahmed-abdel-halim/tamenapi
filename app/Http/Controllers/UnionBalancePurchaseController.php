<?php

namespace App\Http\Controllers;

use App\Models\UnionBalancePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnionBalancePurchaseController extends Controller
{
    public function index()
    {
        $purchases = UnionBalancePurchase::orderBy('purchase_date', 'desc')->get();
        return response()->json([
            'success' => true,
            'data' => $purchases
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'request_number' => 'nullable|string',
            'amount_paid' => 'required|numeric',
            'card_price' => 'required|numeric',
            'union_fee_per_card' => 'required|numeric',
            'supervision_fee_per_card' => 'nullable|numeric',
            'company_deposit_per_card' => 'required|numeric',
            'cards_count' => 'required|integer',
            'total_union_fee' => 'required|numeric',
            'total_supervision_fee' => 'nullable|numeric',
            'total_company_deposit' => 'required|numeric',
            'payment_method' => 'required|string',
            'depositor_name' => 'nullable|string',
            'deposit_date' => 'nullable|date',
            'bank_account_number' => 'nullable|string',
            'deposit_amount' => 'nullable|numeric',
            'purchase_date' => 'required|date',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'bank_deposit_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('union_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        if ($request->hasFile('bank_deposit_image')) {
            $path = $request->file('bank_deposit_image')->store('union_bank_deposits', 'public');
            $data['bank_deposit_image'] = $path;
        }

        $purchase = UnionBalancePurchase::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل طلب رصيد الاتحاد بنجاح',
            'data' => $purchase
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $purchase = UnionBalancePurchase::findOrFail($id);

        $request->validate([
            'request_number' => 'nullable|string',
            'amount_paid' => 'required|numeric',
            'card_price' => 'required|numeric',
            'union_fee_per_card' => 'required|numeric',
            'supervision_fee_per_card' => 'nullable|numeric',
            'company_deposit_per_card' => 'required|numeric',
            'cards_count' => 'required|integer',
            'total_union_fee' => 'required|numeric',
            'total_supervision_fee' => 'nullable|numeric',
            'total_company_deposit' => 'required|numeric',
            'payment_method' => 'required|string',
            'depositor_name' => 'nullable|string',
            'deposit_date' => 'nullable|date',
            'bank_account_number' => 'nullable|string',
            'deposit_amount' => 'nullable|numeric',
            'purchase_date' => 'required|date',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'bank_deposit_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();

        // Handle deleting or replacing union receipt image
        if ($request->boolean('delete_receipt_image') || $request->input('delete_receipt_image') == '1') {
            if ($purchase->receipt_image) {
                Storage::disk('public')->delete($purchase->receipt_image);
            }
            $data['receipt_image'] = null;
        }

        if ($request->hasFile('receipt_image')) {
            if ($purchase->receipt_image) {
                Storage::disk('public')->delete($purchase->receipt_image);
            }
            $path = $request->file('receipt_image')->store('union_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        // Handle deleting or replacing bank deposit image
        if ($request->boolean('delete_bank_deposit_image') || $request->input('delete_bank_deposit_image') == '1') {
            if ($purchase->bank_deposit_image) {
                Storage::disk('public')->delete($purchase->bank_deposit_image);
            }
            $data['bank_deposit_image'] = null;
        }

        if ($request->hasFile('bank_deposit_image')) {
            if ($purchase->bank_deposit_image) {
                Storage::disk('public')->delete($purchase->bank_deposit_image);
            }
            $path = $request->file('bank_deposit_image')->store('union_bank_deposits', 'public');
            $data['bank_deposit_image'] = $path;
        }

        $purchase->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طلب رصيد الاتحاد بنجاح',
            'data' => $purchase
        ]);
    }

    public function destroy($id)
    {
        $purchase = UnionBalancePurchase::findOrFail($id);

        if ($purchase->receipt_image) {
            Storage::disk('public')->delete($purchase->receipt_image);
        }

        if ($purchase->bank_deposit_image) {
            Storage::disk('public')->delete($purchase->bank_deposit_image);
        }

        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف سجل رصيد الاتحاد بنجاح'
        ]);
    }
}
