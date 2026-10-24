<?php

namespace App\Http\Controllers;

use App\Models\CompulsoryDocumentRequest;
use App\Models\StoreItem;
use App\Models\InventoryStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CompulsoryDocumentRequestController extends Controller
{
    use \App\Traits\HandlesBranchScope;

    /**
     * List all compulsory document requests with stats and filters.
     */
    public function index(Request $request)
    {
        $query = CompulsoryDocumentRequest::with('storeItem:id,name,unit')
            ->orderBy('request_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('request_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('request_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('signatory_name', 'like', "%{$search}%");
            });
        }

        $allRequests = (clone $query)->get();

        // Calculate statistics across all requests (or filtered)
        $baseQuery = CompulsoryDocumentRequest::query();
        $this->applyBranchScope($baseQuery, $request);
        $totalRequests = $baseQuery->count();
        $totalRequestedDocs = (int)$baseQuery->sum('documents_count');
        $totalAmountPaidOrDue = (float)$baseQuery->sum('total_amount');
        $totalReceivedDocs = (int)$baseQuery->where('status', 'received')->sum('received_quantity');
        $pendingCount = $baseQuery->whereIn('status', ['draft', 'pending_approval', 'pending_payment', 'sent'])->count();
        $receivedCount = $baseQuery->where('status', 'received')->count();

        $storeItems = StoreItem::select('id', 'name', 'unit', 'category')->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $allRequests,
            'statistics' => [
                'total_requests' => $totalRequests,
                'total_requested_docs' => $totalRequestedDocs,
                'total_amount' => $totalAmountPaidOrDue,
                'total_received_docs' => $totalReceivedDocs,
                'pending_count' => $pendingCount,
                'received_count' => $receivedCount,
            ],
            'store_items' => $storeItems,
        ]);
    }

    /**
     * Create a new request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'documents_count' => 'required|integer|min:1',
            'fee_per_document' => 'nullable|numeric|min:0',
            'request_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'amount_in_words' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:150',
            'status' => 'nullable|in:draft,pending_approval,pending_payment,sent,received,canceled',
            'signatory_name' => 'nullable|string|max:150',
            'signatory_title' => 'nullable|string|max:150',
            'subject' => 'nullable|string|max:255',
            'addressed_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240',
        ]);

        $docsCount = (int)$validated['documents_count'];
        $feePerDoc = isset($validated['fee_per_document']) ? (float)$validated['fee_per_document'] : 5.00;
        $totalAmount = $docsCount * $feePerDoc;

        // Auto generate request number if not passed
        $year = date('Y', strtotime($validated['request_date']));
        $month = date('m', strtotime($validated['request_date']));
        $count = CompulsoryDocumentRequest::whereYear('request_date', $year)->count() + 1;
        $requestNumber = sprintf("REQ-%s-%04d", $year, $count);

        // Auto generate reference number if empty (e.g. أ / 9 - 2026)
        $refNumber = $validated['reference_number'] ?? null;
        if (empty($refNumber)) {
            $refNumber = sprintf("أ / %d - %s", (int)$month, $year);
        }

        $attachmentsList = [];
        if ($request->hasFile('attachments')) {
            $files = $request->file('attachments');
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if ($file && $file->isValid()) {
                    $attachmentsList[] = $file->store('compulsory_requests', 'public');
                }
            }
        }

        if ($request->hasFile('payment_receipt_image')) {
            $primary = $request->file('payment_receipt_image')->store('compulsory_requests', 'public');
            if (!in_array($primary, $attachmentsList)) {
                array_unshift($attachmentsList, $primary);
            }
        }

        $imagePath = !empty($attachmentsList) ? $attachmentsList[0] : null;

        $recordData = [
            'request_number' => $requestNumber,
            'reference_number' => $refNumber,
            'request_date' => $validated['request_date'],
            'documents_count' => $docsCount,
            'fee_per_document' => $feePerDoc,
            'total_amount' => $totalAmount,
            'amount_in_words' => $validated['amount_in_words'] ?? null,
            'payment_method' => $validated['payment_method'] ?? 'صك مصرفي',
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_receipt_image' => $imagePath,
            'status' => $validated['status'] ?? 'draft',
            'signatory_name' => $validated['signatory_name'] ?? 'محمد حوشي',
            'signatory_title' => $validated['signatory_title'] ?? 'مدير الشؤون الإدارية',
            'subject' => $validated['subject'] ?? 'طلب تزويد وثائق التأمين الإجباري',
            'addressed_to' => $validated['addressed_to'] ?? 'السيد / مدير عام هيئة الإشراف على التأمين',
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id() ?? null,
        ];

        if (Schema::hasColumn('compulsory_document_requests', 'attachments')) {
            $recordData['attachments'] = !empty($attachmentsList) ? $attachmentsList : null;
        }

        $record = CompulsoryDocumentRequest::create($recordData);

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء طلب تزويد وثائق التأمين الإجباري بنجاح',
            'data' => $record->load('storeItem:id,name,unit'),
        ], 201);
    }

    /**
     * Update an existing request.
     */
    public function update(Request $request, $id)
    {
        $record = CompulsoryDocumentRequest::findOrFail($id);

        $validated = $request->validate([
            'documents_count' => 'required|integer|min:1',
            'fee_per_document' => 'nullable|numeric|min:0',
            'request_date' => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'amount_in_words' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:150',
            'status' => 'nullable|in:draft,pending_approval,pending_payment,sent,received,canceled',
            'signatory_name' => 'nullable|string|max:150',
            'signatory_title' => 'nullable|string|max:150',
            'subject' => 'nullable|string|max:255',
            'addressed_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'payment_receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf|max:10240',
            'existing_attachments' => 'nullable',
        ]);

        $docsCount = (int)$validated['documents_count'];
        $feePerDoc = isset($validated['fee_per_document']) ? (float)$validated['fee_per_document'] : $record->fee_per_document;
        $totalAmount = $docsCount * $feePerDoc;

        $data = [
            'reference_number' => $validated['reference_number'] ?? $record->reference_number,
            'request_date' => $validated['request_date'],
            'documents_count' => $docsCount,
            'fee_per_document' => $feePerDoc,
            'total_amount' => $totalAmount,
            'amount_in_words' => $validated['amount_in_words'] ?? $record->amount_in_words,
            'payment_method' => $validated['payment_method'] ?? $record->payment_method,
            'payment_reference' => $validated['payment_reference'] ?? $record->payment_reference,
            'status' => $validated['status'] ?? $record->status,
            'signatory_name' => $validated['signatory_name'] ?? $record->signatory_name,
            'signatory_title' => $validated['signatory_title'] ?? $record->signatory_title,
            'subject' => $validated['subject'] ?? $record->subject,
            'addressed_to' => $validated['addressed_to'] ?? $record->addressed_to,
            'notes' => $validated['notes'] ?? $record->notes,
        ];

        // Manage attachments list
        $currentList = $record->attachments_list ?: [];

        if ($request->has('existing_attachments')) {
            $existing = $request->input('existing_attachments');
            if (is_string($existing)) {
                $decoded = json_decode($existing, true);
                $existing = is_array($decoded) ? $decoded : [];
            }
            if (!is_array($existing)) {
                $existing = [];
            }
            // Delete removed files from disk
            foreach ($currentList as $oldFile) {
                if (!in_array($oldFile, $existing)) {
                    Storage::disk('public')->delete($oldFile);
                }
            }
            $currentList = $existing;
        }

        // Delete all receipt files if requested
        if ($request->boolean('delete_receipt_image') || $request->input('delete_receipt_image') == '1') {
            foreach ($currentList as $file) {
                Storage::disk('public')->delete($file);
            }
            if ($record->payment_receipt_image) {
                Storage::disk('public')->delete($record->payment_receipt_image);
            }
            $currentList = [];
        }

        // Upload any new attachments
        if ($request->hasFile('attachments')) {
            $newFiles = $request->file('attachments');
            if (!is_array($newFiles)) {
                $newFiles = [$newFiles];
            }
            foreach ($newFiles as $file) {
                if ($file && $file->isValid()) {
                    $currentList[] = $file->store('compulsory_requests', 'public');
                }
            }
        }

        if ($request->hasFile('payment_receipt_image')) {
            $newPrimary = $request->file('payment_receipt_image')->store('compulsory_requests', 'public');
            if (!in_array($newPrimary, $currentList)) {
                $currentList[] = $newPrimary;
            }
        }

        $data['payment_receipt_image'] = !empty($currentList) ? $currentList[0] : null;

        if (Schema::hasColumn('compulsory_document_requests', 'attachments')) {
            $data['attachments'] = !empty($currentList) ? array_values($currentList) : null;
        }

        $record->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طلب التزويد بنجاح',
            'data' => $record->load('storeItem:id,name,unit'),
        ]);
    }

    /**
     * Update request status directly.
     */
    public function updateStatus(Request $request, $id)
    {
        $record = CompulsoryDocumentRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,pending_approval,pending_payment,sent,received,canceled',
        ]);

        $record->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير حالة الطلب بنجاح',
            'data' => $record,
        ]);
    }

    /**
     * Receive documents and link directly to inventory stock.
     */
    public function receive(Request $request, $id)
    {
        $record = CompulsoryDocumentRequest::findOrFail($id);

        $validated = $request->validate([
            'received_quantity' => 'required|integer|min:1',
            'received_date' => 'required|date',
            'serial_from' => 'nullable|string|max:100',
            'serial_to' => 'nullable|string|max:100',
            'store_item_id' => 'nullable|exists:store_items,id',
            'warehouse_location' => 'nullable|string|max:150',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $receivedQty = (int)$validated['received_quantity'];
            $storeItemId = $validated['store_item_id'] ?? null;
            $location = $validated['warehouse_location'] ?: 'المخزن الرئيسي';

            // If a store item was chosen, increase inventory stock
            if ($storeItemId) {
                $stock = InventoryStock::where('item_id', $storeItemId)->first();
                if (!$stock) {
                    InventoryStock::create([
                        'item_id' => $storeItemId,
                        'quantity' => $receivedQty,
                        'warehouse_location' => $location,
                    ]);
                } else {
                    $stock->quantity += $receivedQty;
                    $stock->save();
                }
            }

            $record->update([
                'status' => 'received',
                'received_quantity' => $receivedQty,
                'received_date' => $validated['received_date'],
                'serial_from' => $validated['serial_from'] ?? null,
                'serial_to' => $validated['serial_to'] ?? null,
                'store_item_id' => $storeItemId,
                'warehouse_location' => $location,
                'notes' => $validated['notes'] ? ($record->notes . " | " . $validated['notes']) : $record->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل استلام الوثائق وتغذية المخزون بنجاح',
                'data' => $record->load('storeItem:id,name,unit'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الاستلام: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a request.
     */
    public function destroy($id)
    {
        $record = CompulsoryDocumentRequest::findOrFail($id);

        $files = $record->attachments_list ?: [];
        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }
        if ($record->payment_receipt_image) {
            Storage::disk('public')->delete($record->payment_receipt_image);
        }

        $record->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف طلب التزويد بنجاح',
        ]);
    }
}
