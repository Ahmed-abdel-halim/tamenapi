<?php

namespace App\Http\Controllers;

use App\Models\UnionBalancePurchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UnionBalancePurchaseController extends Controller
{
    /**
     * Display a listing of union purchases.
     */
    public function index()
    {
        $purchases = UnionBalancePurchase::orderBy('purchase_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalDepositPurchases = UnionBalancePurchase::sum('total_company_deposit');
        $totalCards = UnionBalancePurchase::sum('cards_count');

        // Deduct indemnities that are paid from the union deposit
        $orangeCardIndemnities = \App\Models\Expense::where('is_indemnity', true)
            ->where('indemnity_type', 'orange_card')
            ->sum('amount');

        $netDeposit = $totalDepositPurchases - $orangeCardIndemnities;

        return response()->json([
            'success' => true,
            'data' => $purchases,
            'statistics' => [
                'total_deposit' => (float)max(0, $netDeposit),
                'original_deposit' => (float)$totalDepositPurchases,
                'total_cards' => $totalCards,
                'total_indemnities_deducted' => (float)$orangeCardIndemnities,
            ]
        ]);
    }

    /**
     * Store a new union purchase.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount_paid' => 'required|numeric',
            'card_price' => 'required|numeric',
            'union_fee_per_card' => 'required|numeric',
            'company_deposit_per_card' => 'required|numeric',
            'purchase_date' => 'required|date',
            'receipt_image' => 'nullable|image|max:2048',
        ]);

        try {
            $data = $request->all();
            
            // Calculate derived fields
            $paid = (float)$request->amount_paid;
            $price = (float)$request->card_price;
            $cardsCount = $price > 0 ? floor($paid / $price) : 0;
            
            $data['cards_count'] = $cardsCount;
            $data['total_union_fee'] = $cardsCount * (float)$request->union_fee_per_card;
            $data['total_company_deposit'] = $cardsCount * (float)$request->company_deposit_per_card;

            if ($request->hasFile('receipt_image')) {
                $path = $request->file('receipt_image')->store('union_receipts', 'public');
                $data['receipt_image'] = '/storage/' . $path;
            }

            $purchase = UnionBalancePurchase::create($data);

            return response()->json([
                'success' => true,
                'data' => $purchase
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified purchase.
     */
    public function destroy($id)
    {
        $purchase = UnionBalancePurchase::findOrFail($id);
        
        // Delete image if exists
        if ($purchase->receipt_image) {
            $path = str_replace('/storage/', '', $purchase->receipt_image);
            Storage::disk('public')->delete($path);
        }

        $purchase->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }
}
