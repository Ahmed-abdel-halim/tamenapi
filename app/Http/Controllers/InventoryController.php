<?php

namespace App\Http\Controllers;

use App\Models\StoreItem;
use App\Models\InventoryStock;
use App\Models\FixedCustody;
use App\Models\CustodyMovement;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    // --- Store Items Management ---

    public function itemsIndex()
    {
        $items = StoreItem::with('stocks')->get()->map(function ($item) {
            $stocks = $item->stocks ?? collect();
            $totalQuantity = (int) $stocks->sum('quantity');
            $latestStock = $stocks->sortByDesc('updated_at')->first();

            // Return a normalized single-stock view to keep frontend display stable.
            $item->setRelation('stocks', collect([[
                'item_id' => $item->id,
                'quantity' => $totalQuantity,
                'warehouse_location' => $latestStock->warehouse_location ?? null,
            ]]));

            return $item;
        });

        return response()->json($items->values());
    }

    public function storeItem(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string|max:100',
            'inventory_type' => 'required|in:fixed,consumable',
            'unit' => 'nullable|string',
            'serial_prefix' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'min_threshold' => 'integer',
        ]);

        $item = StoreItem::create($validated);
        
        // Initialize stock
        InventoryStock::create([
            'item_id' => $item->id,
            'quantity' => 0
        ]);

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, $id)
    {
        $item = StoreItem::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string|max:100',
            'inventory_type' => 'required|in:fixed,consumable',
            'unit' => 'nullable|string',
            'serial_prefix' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'min_threshold' => 'integer',
        ]);

        $item->update($validated);
        return response()->json($item->fresh('stocks'));
    }

    public function destroyItem($id)
    {
        $item = StoreItem::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'تم حذف الصنف بنجاح']);
    }

    // --- Stock Management ---

    public function updateStock(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:store_items,id',
            'quantity' => 'required|integer', // Can be positive (add) or negative (subtract)
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Keep one canonical stock row per item and merge any duplicates if they exist.
        $stock = InventoryStock::where('item_id', $validated['item_id'])->orderBy('id')->first();
        if (!$stock) {
            $stock = InventoryStock::create([
                'item_id' => $validated['item_id'],
                'quantity' => 0,
            ]);
        }

        $duplicateStocks = InventoryStock::where('item_id', $validated['item_id'])
            ->where('id', '!=', $stock->id)
            ->orderByDesc('updated_at')
            ->get();

        if ($duplicateStocks->isNotEmpty()) {
            $stock->quantity += (int) $duplicateStocks->sum('quantity');
            $latestLocation = $duplicateStocks->pluck('warehouse_location')->filter()->first();
            if ($latestLocation) {
                $stock->warehouse_location = $latestLocation;
            }
            InventoryStock::whereIn('id', $duplicateStocks->pluck('id')->all())->delete();
        }

        $stock->quantity += $validated['quantity'];
        $stock->warehouse_location = $validated['location'] ?? $stock->warehouse_location;
        $stock->save();

        return response()->json($stock);
    }

    // --- Custody Management ---

    public function custodyIndex(Request $request)
    {
        $query = FixedCustody::with(['item', 'recipient']);
        
        if ($request->has('recipient_type')) {
            $type = $request->recipient_type === 'agent' ? BranchAgent::class : User::class;
            $query->where('recipient_type', $type);
        }

        return response()->json($query->orderBy('assigned_at', 'desc')->get());
    }

    public function movementsIndex(Request $request)
    {
        $query = CustodyMovement::with(['item', 'recipient', 'processor'])->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('recipient_type')) {
            $type = $request->recipient_type === 'agent' ? BranchAgent::class : User::class;
            $query->where('recipient_type', $type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $movements = $query->limit(1000)->get()->map(function ($movement) {
            $recipientType = $movement->recipient_type === BranchAgent::class ? 'agent' : 'employee';
            $recipientName = optional($movement->recipient)->agency_name
                ?? optional($movement->recipient)->name
                ?? '-';

            return [
                'id' => $movement->id,
                'type' => $movement->type,
                'quantity' => $movement->quantity,
                'notes' => $movement->notes,
                'created_at' => $movement->created_at,
                'item' => [
                    'id' => optional($movement->item)->id,
                    'name' => optional($movement->item)->name,
                    'inventory_type' => optional($movement->item)->inventory_type ?? 'consumable',
                ],
                'recipient' => [
                    'id' => $movement->recipient_id,
                    'type' => $recipientType,
                    'name' => $recipientName,
                ],
                'processor' => [
                    'id' => optional($movement->processor)->id,
                    'name' => optional($movement->processor)->name ?? '-',
                ],
            ];
        });

        return response()->json($movements->values());
    }

    public function assignCustody(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:store_items,id',
            'recipient_id' => 'required|integer',
            'recipient_type' => 'required|in:agent,employee',
            'inventory_type' => 'nullable|in:fixed,consumable',
            'batch_ref' => 'nullable|string|max:64',
            'quantity' => 'required|integer|min:1',
            'serial_start' => 'nullable|string',
            'serial_end' => 'nullable|string',
            'condition' => 'required|in:new,used',
            'notes' => 'nullable|string',
        ]);

        $item = StoreItem::findOrFail($validated['item_id']);
        if (!empty($validated['inventory_type']) && ($item->inventory_type ?? 'consumable') !== $validated['inventory_type']) {
            return response()->json(['message' => 'نوع الصنف لا يطابق نوع المخزون المحدد'], 422);
        }

        DB::beginTransaction();
        try {
            $stock = InventoryStock::where('item_id', $validated['item_id'])->first();
            if (!$stock || $stock->quantity < $validated['quantity']) {
                return response()->json(['message' => 'الكمية غير متوفرة في المخزن'], 400);
            }

            $recipientType = $validated['recipient_type'] === 'agent' ? BranchAgent::class : User::class;

            $custody = FixedCustody::create([
                'item_id' => $validated['item_id'],
                'recipient_id' => $validated['recipient_id'],
                'recipient_type' => $recipientType,
                'quantity' => $validated['quantity'],
                'serial_start' => $validated['serial_start'],
                'serial_end' => $validated['serial_end'],
                'batch_ref' => $validated['batch_ref'] ?? null,
                'assigned_at' => now(),
                'condition' => $validated['condition'],
                'notes' => $validated['notes'],
            ]);

            // Deduct from stock
            $stock->quantity -= $validated['quantity'];
            $stock->save();

            // Record movement
            CustodyMovement::create([
                'item_id' => $validated['item_id'],
                'recipient_id' => $validated['recipient_id'],
                'recipient_type' => $recipientType,
                'quantity' => $validated['quantity'],
                'type' => 'issue',
                'processed_by' => auth()->id() ?? 1, // Fallback to 1 for testing
                'notes' => 'صرف عهدة جديدة',
            ]);

            DB::commit();
            return response()->json($custody, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ في العملية', 'error' => $e->getMessage()], 500);
        }
    }

    public function returnCustody(Request $request, $id)
    {
        $custody = FixedCustody::findOrFail($id);
        
        DB::beginTransaction();
        try {
            $custody->status = 'returned';
            $custody->save();

            // Return to stock
            $stock = InventoryStock::firstOrCreate(['item_id' => $custody->item_id]);
            $stock->quantity += $custody->quantity;
            $stock->save();

            // Record movement
            CustodyMovement::create([
                'item_id' => $custody->item_id,
                'recipient_id' => $custody->recipient_id,
                'recipient_type' => $custody->recipient_type,
                'quantity' => $custody->quantity,
                'type' => 'return',
                'processed_by' => auth()->id() ?? 1,
                'notes' => $request->notes ?? 'إرجاع عهدة',
            ]);

            DB::commit();
            return response()->json(['message' => 'تم إرجاع العهدة للمخزن بنجاح']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'خطأ في العملية', 'error' => $e->getMessage()], 500);
        }
    }
}
