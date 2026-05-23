<?php

namespace App\Http\Controllers;

use App\Models\AgentTransfer;
use App\Models\BranchAgent;
use App\Models\PaymentVoucher;
use App\Models\TreasuryTransaction;
use App\Models\BankTransaction;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AgentTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Check if user is an agent
        $branchAgentId = $user->branchAgent?->id;
        
        $query = AgentTransfer::with(['agent', 'posMachine', 'creator', 'approver']);
        
        if ($branchAgentId) {
            // Agent sees only their own transfers
            $query->where('branch_agent_id', $branchAgentId);
        } else {
            // Admin/Accountant can filter by agent
            if ($request->filled('branch_agent_id')) {
                $query->where('branch_agent_id', $request->branch_agent_id);
            }
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }
        
        if ($request->filled('from_date')) {
            $query->whereDate('transfer_date', '>=', $request->from_date);
        }
        
        if ($request->filled('to_date')) {
            $query->whereDate('transfer_date', '<=', $request->to_date);
        }
        
        $transfers = $query->orderBy('transfer_date', 'desc')
                           ->orderBy('created_at', 'desc')
                           ->get();
                           
        return response()->json([
            'success' => true,
            'data' => $transfers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $branchAgentId = $user->branchAgent?->id;
        
        $rules = [
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transfer_date' => 'required|date',
            'reference_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'source_bank' => 'nullable|string',
            'source_account_number' => 'nullable|string',
            'pos_machine_id' => 'nullable|integer',
            'representative_name' => 'nullable|string',
            'exchange_office' => 'nullable|string',
            'notes' => 'nullable|string',
            'voucher_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240',
        ];
        
        // If logged-in user is not an agent, they must specify the agent
        if (!$branchAgentId) {
            $rules['branch_agent_id'] = 'required|exists:branches_agents,id';
        }
        
        $request->validate($rules);
        
        $data = $request->except('voucher_image');
        
        if ($branchAgentId) {
            $data['branch_agent_id'] = $branchAgentId;
            $data['status'] = 'pending';
        } else {
            // Admin inserts directly as approved
            $data['status'] = 'approved';
            $data['approved_by'] = $user->id;
            $data['approval_date'] = Carbon::now();
        }
        
        $data['created_by'] = $user->id;
        
        if ($request->hasFile('voucher_image')) {
            $path = $request->file('voucher_image')->store('agent_vouchers', 'public');
            $data['voucher_image'] = $path;
        }
        
        DB::beginTransaction();
        try {
            $transfer = AgentTransfer::create($data);
            
            // If admin added this directly, auto-approve and generate transactions
            if (!$branchAgentId) {
                $this->processApproval($transfer);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $branchAgentId ? 'تم تقديم طلب الحوالة بنجاح وهو قيد التدقيق' : 'تم تسجيل العملية المالية للوكيل بنجاح',
                'data' => $transfer->load('agent')
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($data['voucher_image'])) {
                Storage::disk('public')->delete($data['voucher_image']);
            }
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التحويل',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = auth()->user();
        $branchAgentId = $user->branchAgent?->id;
        
        $transfer = AgentTransfer::with(['agent', 'posMachine', 'creator', 'approver'])->findOrFail($id);
        
        if ($branchAgentId && $transfer->branch_agent_id !== $branchAgentId) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بعرض هذا التحويل'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'data' => $transfer
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $branchAgentId = $user->branchAgent?->id;
        
        $transfer = AgentTransfer::findOrFail($id);
        
        if ($branchAgentId) {
            // Agent can only update their own pending transfers
            if ($transfer->branch_agent_id !== $branchAgentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا التحويل'
                ], 403);
            }
            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تعديل التحويل بعد مطابقته أو رفضه'
                ], 400);
            }
            
            $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string',
                'transfer_date' => 'required|date',
                'reference_number' => 'nullable|string',
                'notes' => 'nullable|string',
                'voucher_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240',
            ]);
            
            $data = $request->except('voucher_image');
            
            if ($request->hasFile('voucher_image')) {
                if ($transfer->voucher_image) {
                    Storage::disk('public')->delete($transfer->voucher_image);
                }
                $path = $request->file('voucher_image')->store('agent_vouchers', 'public');
                $data['voucher_image'] = $path;
            }
            
            $transfer->update($data);
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات التحويل بنجاح',
                'data' => $transfer
            ]);
        }
        
        // Admin/Accountant updating the transfer (Matching or Rejecting)
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|string|nullable'
        ]);
        
        if ($transfer->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'هذه المعاملة تم معالجتها مسبقاً'
            ], 400);
        }
        
        DB::beginTransaction();
        try {
            if ($request->status === 'approved') {
                $transfer->status = 'approved';
                $transfer->approved_by = $user->id;
                $transfer->approval_date = Carbon::now();
                
                $this->processApproval($transfer);
            } else {
                $transfer->status = 'rejected';
                $transfer->rejection_reason = $request->rejection_reason;
                $transfer->approved_by = $user->id;
                $transfer->approval_date = Carbon::now();
            }
            
            $transfer->save();
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => $request->status === 'approved' ? 'تمت مطابقة الحوالة وتأكيدها وتوليد المستندات بنجاح' : 'تم رفض الحوالة وإخطار الوكيل',
                'data' => $transfer->load(['agent', 'approver'])
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معالجة العملية',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $branchAgentId = $user->branchAgent?->id;
        
        $transfer = AgentTransfer::findOrFail($id);
        
        if ($branchAgentId) {
            if ($transfer->branch_agent_id !== $branchAgentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بحذف هذا التحويل'
                ], 403);
            }
            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف الحوالة بعد مطابقتها أو رفضها'
                ], 400);
            }
        } else {
            // Admin can delete, but if it is approved we must delete generated docs as well
            if ($transfer->status === 'approved') {
                DB::beginTransaction();
                try {
                    $this->deleteGeneratedDocuments($transfer);
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'حدث خطأ أثناء حذف المستندات المرتبطة',
                        'error' => $e->getMessage()
                    ], 500);
                }
            }
        }
        
        if ($transfer->voucher_image) {
            Storage::disk('public')->delete($transfer->voucher_image);
        }
        
        $transfer->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'تم حذف التحويل بنجاح'
        ]);
    }

    /**
     * Process the approval of an agent transfer, generating ledger and invoice vouchers.
     */
    private function processApproval(AgentTransfer $transfer)
    {
        // 1. Generate Arabic Payment Method description
        $methodArabic = $this->getArabicPaymentMethod($transfer->payment_method);
        
        // 2. Create Payment Voucher (سند قبض) - This will reduce agent debt in accounts
        $pv = PaymentVoucher::create([
            'voucher_number' => 'PV-T-' . str_pad($transfer->id, 5, '0', STR_PAD_LEFT),
            'branch_agent_id' => $transfer->branch_agent_id,
            'amount' => $transfer->amount,
            'payment_method' => $methodArabic,
            'bank_name' => $transfer->bank_name,
            'reference_number' => $transfer->reference_number,
            'payment_date' => $transfer->transfer_date,
            'notes' => $transfer->notes ?? ('حوالة مطابقة من الوكيل: ' . ($transfer->agent->agency_name ?? $transfer->branch_agent_id)),
            'extra_details' => [
                'agent_transfer_id' => $transfer->id,
                'representative_name' => $transfer->representative_name,
                'exchange_office' => $transfer->exchange_office,
                'source_bank' => $transfer->source_bank,
                'source_account_number' => $transfer->source_account_number,
            ]
        ]);
        
        $transfer->payment_voucher_id = $pv->id;
        
        // 3. Dispatch to accounting ledger based on payment method
        $bankMethods = ['bank_deposit', 'mobile_payment', 'bank_cheque', 'bank_transfer'];
        $cashMethods = ['cash_office', 'cash_representative'];
        
        if (in_array($transfer->payment_method, $bankMethods)) {
            // Bank Transaction
            $bt = BankTransaction::create([
                'transaction_date' => $transfer->transfer_date,
                'reference_number' => $transfer->reference_number,
                'bank_name' => $transfer->bank_name ?? 'غير محدد',
                'amount' => $transfer->amount,
                'type' => 'deposit',
                'reconciled' => true,
                'notes' => $transfer->notes ?? "حوالة وكيل مطابقة: " . ($transfer->agent->agency_name ?? ''),
                'transaction_type' => $methodArabic,
                'source_bank' => $transfer->source_bank,
                'source_account_number' => $transfer->source_account_number,
                'branch_agent_id' => $transfer->branch_agent_id,
                'agent_name' => $transfer->agent->agency_name ?? null,
                'voucher_image' => $transfer->voucher_image,
            ]);
            $transfer->bank_transaction_id = $bt->id;
            
        } elseif (in_array($transfer->payment_method, $cashMethods)) {
            // Treasury Transaction
            $description = "حوالة وكيل: " . ($transfer->agent->agency_name ?? 'وكيل') . " - " . $methodArabic;
            if ($transfer->payment_method === 'cash_office' && $transfer->exchange_office) {
                $description .= " (مكتب: " . $transfer->exchange_office . ")";
            } elseif ($transfer->payment_method === 'cash_representative' && $transfer->representative_name) {
                $description .= " (المندوب: " . $transfer->representative_name . ")";
            }
            
            $tt = TreasuryTransaction::create([
                'transaction_date' => $transfer->transfer_date,
                'type' => 'income',
                'amount' => $transfer->amount,
                'description' => $description,
                'source' => $transfer->agent->agency_name ?? 'وكيل',
                'reference_number' => $transfer->reference_number,
                'voucher_image' => $transfer->voucher_image,
                'branch_agent_id' => $transfer->branch_agent_id,
                'payment_source' => $methodArabic,
                'notes' => $transfer->notes,
            ]);
            $transfer->treasury_transaction_id = $tt->id;
            
        } elseif ($transfer->payment_method === 'pos_machine') {
            // POS Machine Transaction
            $pt = PosTransaction::create([
                'pos_machine_id' => $transfer->pos_machine_id,
                'transaction_date' => $transfer->transfer_date,
                'amount' => $transfer->amount,
                'transactions_count' => 1,
                'reference_number' => $transfer->reference_number,
                'report_file' => $transfer->voucher_image,
                'is_reconciled' => true,
                'notes' => "مبيعات ماكينة وكيل: " . ($transfer->agent->agency_name ?? '') . " | " . ($transfer->notes ?? ''),
            ]);
            $transfer->pos_transaction_id = $pt->id;
        }
        
        $transfer->save();
    }

    /**
     * Delete generated ledger records if a transfer is deleted.
     */
    private function deleteGeneratedDocuments(AgentTransfer $transfer)
    {
        if ($transfer->payment_voucher_id) {
            PaymentVoucher::where('id', $transfer->payment_voucher_id)->delete();
        }
        if ($transfer->bank_transaction_id) {
            BankTransaction::where('id', $transfer->bank_transaction_id)->delete();
        }
        if ($transfer->treasury_transaction_id) {
            TreasuryTransaction::where('id', $transfer->treasury_transaction_id)->delete();
        }
        if ($transfer->pos_transaction_id) {
            PosTransaction::where('id', $transfer->pos_transaction_id)->delete();
        }
    }

    /**
     * Get Arabic representation of payment methods.
     */
    private function getArabicPaymentMethod($method)
    {
        $map = [
            'bank_deposit' => 'إيداع في الحساب',
            'mobile_payment' => 'دفع عن طريق الموبايل',
            'bank_cheque' => 'صك مصرفي',
            'bank_transfer' => 'حوالة مصرفية',
            'cash_office' => 'حوالة مالية كاش (مكتب)',
            'cash_representative' => 'نقداً تسليم للمندوب',
            'pos_machine' => 'مبيعات ماكينة (POS)',
        ];
        
        return $map[$method] ?? $method;
    }
}
