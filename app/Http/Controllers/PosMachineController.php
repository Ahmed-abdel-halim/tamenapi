<?php

namespace App\Http\Controllers;

use App\Models\PosMachine;
use App\Models\PosCustodyMovement;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PosMachineController extends Controller
{
    private function resolveUser()
    {
        $user = auth()->user() ?? auth('sanctum')->user();
        if (!$user) {
            $bearer = request()->bearerToken();
            if ($bearer) {
                $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
                if ($tokenModel) {
                    $user = $tokenModel->tokenable;
                }
            }
        }
        if (!$user) {
            $userId = request()->header('X-User-Id') ?? request()->input('user_id');
            if ($userId) {
                $user = \App\Models\User::find($userId);
            }
        }
        return $user;
    }

    private function checkPermission($permission)
    {
        $user = $this->resolveUser();
        if (!$user) {
            return true;
        }
        if ($user->is_admin) {
            return true;
        }
        $authorized = $user->authorized_documents ?? [];
        if (!is_array($authorized)) {
            return false;
        }
        return in_array($permission, $authorized) || 
               in_array('المطابقة والتحصيلات المالية', $authorized) || 
               in_array('المحاسب المالي', $authorized) ||
               in_array('المصارف والخزنة', $authorized) ||
               in_array('إدخال مبيعات نقاط البيع (POS)', $authorized) ||
               in_array('مطابقة مبيعات نقاط البيع (POS)', $authorized);
    }

    private function hasPosAccess()
    {
        $user = $this->resolveUser();
        if (!$user) {
            return true;
        }
        if ($user->is_admin) {
            return true;
        }
        $authorized = $user->authorized_documents ?? [];
        if (!is_array($authorized)) {
            return false;
        }
        return in_array('المطابقة والتحصيلات المالية', $authorized) || 
               in_array('المحاسب المالي', $authorized) ||
               in_array('المصارف والخزنة', $authorized) ||
               in_array('إدخال مبيعات نقاط البيع (POS)', $authorized) ||
               in_array('مطابقة مبيعات نقاط البيع (POS)', $authorized);
    }

    // ─── ماكينات POS ────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى هذه الصفحة'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $query = PosMachine::withCount('transactions')
            ->withSum('transactions', 'amount')
            ->with('branchAgents')
            ->orderBy('machine_name');

        if ($branchAgentId) {
            $query->where(function($q) use ($branchAgentId) {
                $q->whereHas('branchAgents', function ($sub) use ($branchAgentId) {
                    $sub->where('branches_agents.id', $branchAgentId);
                })->orWhere('current_agent_id', $branchAgentId);
            });
        } elseif ($request->filled('branch_agent_id') && $request->boolean('only_agent')) {
            $agentId = (int)$request->branch_agent_id;
            $query->where(function($q) use ($agentId) {
                $q->whereHas('branchAgents', function ($sub) use ($agentId) {
                    $sub->where('branches_agents.id', $agentId);
                })->orWhere('current_agent_id', $agentId);
            });
        }

        $machines = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $machines,
        ]);
    }

    public function store(Request $request)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعريف أو تعديل ماكينات POS'], 403);
        }
        $request->validate([
            'machine_name'      => 'required|string',
            'machine_serial'    => 'nullable|string',
            'bank_name'         => 'required|string',
            'merchant_id'       => 'nullable|string',
            'location'          => 'nullable|string',
            'is_active'         => 'nullable|boolean',
            'notes'             => 'nullable|string',
            'branch_agent_ids'   => 'nullable|array',
            'branch_agent_ids.*' => 'exists:branches_agents,id',
        ]);

        $machine = PosMachine::create($request->only([
            'machine_name', 'machine_serial', 'bank_name', 'merchant_id', 'location', 'is_active', 'notes'
        ]));

        if ($request->has('branch_agent_ids')) {
            $machine->branchAgents()->sync($request->input('branch_agent_ids', []));
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الماكينة بنجاح',
            'data'    => $machine->load('branchAgents'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعريف أو تعديل ماكينات POS'], 403);
        }
        $machine = PosMachine::findOrFail($id);

        $request->validate([
            'machine_name'      => 'required|string',
            'bank_name'         => 'required|string',
            'branch_agent_ids'   => 'nullable|array',
            'branch_agent_ids.*' => 'exists:branches_agents,id',
        ]);

        $machine->update($request->only([
            'machine_name', 'machine_serial', 'bank_name', 'merchant_id', 'location', 'is_active', 'notes'
        ]));

        if ($request->has('branch_agent_ids')) {
            $machine->branchAgents()->sync($request->input('branch_agent_ids', []));
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الماكينة بنجاح',
            'data'    => $machine->load('branchAgents'),
        ]);
    }

    public function destroy($id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بحذف ماكينات POS'], 403);
        }
        PosMachine::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الماكينة']);
    }

    public function toggleActive($id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل حالة ماكينات POS'], 403);
        }
        $machine = PosMachine::findOrFail($id);
        $machine->update(['is_active' => !$machine->is_active]);
        return response()->json(['success' => true, 'data' => $machine]);
    }

    // ─── معاملات POS ────────────────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى هذه الصفحة'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $query = PosTransaction::with('machine');

        if ($branchAgentId) {
            $query->whereHas('machine.branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            });
        }

        if ($request->filled('machine_id')) {
            $query->where('pos_machine_id', $request->machine_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        if ($request->filled('is_reconciled')) {
            $query->where('is_reconciled', (bool)$request->is_reconciled);
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        $totalAmount = $transactions->sum('amount');
        $totalCount  = $transactions->sum('transactions_count');

        // إحصائيات الشهر
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();

        $monthStatsQuery = PosTransaction::whereBetween('transaction_date', [$startOfMonth, $endOfMonth]);
        if ($branchAgentId) {
            $monthStatsQuery->whereHas('machine.branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            });
        }
        $monthAmount = $monthStatsQuery->sum('amount');

        return response()->json([
            'success'      => true,
            'data'         => $transactions,
            'stats'        => [
                'total_amount'   => (float) $totalAmount,
                'total_count'    => (int) $totalCount,
                'month_amount'   => (float) $monthAmount,
                'records_count'  => $transactions->count(),
            ],
        ]);
    }

    public function storeTransaction(Request $request)
    {
        if (!$this->checkPermission('إدخال مبيعات نقاط البيع (POS)')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بإضافة معاملات تسوية POS'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $request->validate([
            'pos_machine_id'     => 'required|exists:pos_machines,id',
            'transaction_date'   => 'required|date',
            'amount'             => 'required|numeric|min:0.01',
            'transactions_count' => 'nullable|integer|min:1',
            'reference_number'   => 'nullable|string',
            'notes'              => 'nullable|string',
            'report_file'        => 'nullable|file|mimes:pdf,xlsx,xls,csv,jpg,jpeg,png,webp|max:20480',
        ]);

        if ($branchAgentId) {
            $hasMachine = PosMachine::where('id', $request->pos_machine_id)
                ->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                    $q->where('branches_agents.id', $branchAgentId);
                })->exists();
            if (!$hasMachine) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك باستخدام هذه الماكينة'], 403);
            }
        }

        $data = $request->except('report_file');

        if ($request->hasFile('report_file')) {
            $path = $request->file('report_file')->store('pos_reports', 'public');
            $data['report_file'] = $path;
        }

        $transaction = PosTransaction::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة معاملة POS بنجاح',
            'data'    => $transaction->load('machine'),
        ], 201);
    }

    public function updateTransaction(Request $request, $id)
    {
        if (!$this->checkPermission('إدخال مبيعات نقاط البيع (POS)')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل معاملات تسوية POS'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $transaction = PosTransaction::findOrFail($id);

        if ($branchAgentId) {
            $hasMachine = $transaction->machine()->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            })->exists();
            if (!$hasMachine) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل هذه المعاملة'], 403);
            }
        }

        $request->validate([
            'pos_machine_id'   => 'required|exists:pos_machines,id',
            'transaction_date' => 'required|date',
            'amount'           => 'required|numeric|min:0.01',
            'report_file'      => 'nullable|file|mimes:pdf,xlsx,xls,csv,jpg,jpeg,png,webp|max:20480',
        ]);

        if ($branchAgentId) {
            $hasNewMachine = PosMachine::where('id', $request->pos_machine_id)
                ->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                    $q->where('branches_agents.id', $branchAgentId);
                })->exists();
            if (!$hasNewMachine) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك باستخدام هذه الماكينة'], 403);
            }
        }

        $data = $request->except('report_file');

        if ($request->hasFile('report_file')) {
            if ($transaction->report_file) {
                Storage::disk('public')->delete($transaction->report_file);
            }
            $path = $request->file('report_file')->store('pos_reports', 'public');
            $data['report_file'] = $path;
        }

        $transaction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المعاملة',
            'data'    => $transaction->load('machine'),
        ]);
    }

    public function destroyTransaction($id)
    {
        if (!$this->checkPermission('إدخال مبيعات نقاط البيع (POS)')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بحذف معاملات تسوية POS'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $transaction = PosTransaction::findOrFail($id);

        if ($branchAgentId) {
            $hasMachine = $transaction->machine()->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            })->exists();
            if (!$hasMachine) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بحذف هذه المعاملة'], 403);
            }
        }

        if ($transaction->report_file) {
            Storage::disk('public')->delete($transaction->report_file);
        }

        $transaction->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المعاملة']);
    }

    public function toggleReconcile($id)
    {
        if (!$this->checkPermission('مطابقة مبيعات نقاط البيع (POS)')) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتأكيد أو إلغاء مطابقة معاملات تسوية POS'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $transaction = PosTransaction::findOrFail($id);

        if ($branchAgentId) {
            $hasMachine = $transaction->machine()->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            })->exists();
            if (!$hasMachine) {
                return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل هذه المعاملة'], 403);
            }
        }

        $transaction->update(['is_reconciled' => !$transaction->is_reconciled]);
        return response()->json(['success' => true, 'data' => $transaction]);
    }

    // ─── لوحة قيادة POS ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى هذه الصفحة'], 403);
        }
        $user = $this->resolveUser();
        $branchAgentId = $user ? $user->branchAgent?->id : null;

        $query = PosMachine::where('is_active', true);

        if ($branchAgentId) {
            $query->whereHas('branchAgents', function ($q) use ($branchAgentId) {
                $q->where('branches_agents.id', $branchAgentId);
            });
        }

        $machines = $query->get();

        $machineStats = $machines->map(function ($machine) {
            $monthStart = Carbon::now()->startOfMonth();
            $monthEnd   = Carbon::now()->endOfMonth();

            $monthTotal = PosTransaction::where('pos_machine_id', $machine->id)
                ->whereBetween('transaction_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $totalAll = PosTransaction::where('pos_machine_id', $machine->id)->sum('amount');

            return [
                'id'           => $machine->id,
                'machine_name' => $machine->machine_name,
                'bank_name'    => $machine->bank_name,
                'location'     => $machine->location,
                'month_total'  => (float) $monthTotal,
                'total_all'    => (float) $totalAll,
            ];
        });

        $machineIds = $machines->pluck('id');
        $grandTotal     = PosTransaction::whereIn('pos_machine_id', $machineIds)->sum('amount');
        $monthGrandTotal = PosTransaction::whereIn('pos_machine_id', $machineIds)
            ->whereBetween(
                'transaction_date',
                [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]
            )->sum('amount');

        return response()->json([
            'success'          => true,
            'machine_stats'    => $machineStats,
            'grand_total'      => (float) $grandTotal,
            'month_grand_total'=> (float) $monthGrandTotal,
        ]);
    }

    // ─── عهدة الماكينات (Custody Lifecycle) ────────────────────────────────────

    /**
     * POST /api/pos-machines/{id}/handover
     * تسليم عهدة الجهاز لوكيل مع إنشاء محضر تسليم رسمي
     */
    public function handover(Request $request, $id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتسليم عهدة ماكينات POS'], 403);
        }

        $machine = PosMachine::findOrFail($id);

        if (($machine->custody_status ?? 'available') === 'in_custody') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تسليم هذا الجهاز، فهو بعهدة وكيل حالياً. يجب استرجاعه أولاً.',
            ], 422);
        }

        $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'movement_date'   => 'required|date',
            'device_condition'=> 'nullable|string',
            'accessories'     => 'nullable|array',
            'received_by_name'=> 'nullable|string',
            'notes'           => 'nullable|string',
        ]);

        $user = $this->resolveUser();

        DB::transaction(function () use ($request, $machine, $user) {
            // إنشاء محضر التسليم
            $movement = PosCustodyMovement::create([
                'reference_no'    => PosCustodyMovement::generateReferenceNo('handover'),
                'pos_machine_id'  => $machine->id,
                'branch_agent_id' => $request->branch_agent_id,
                'movement_type'   => 'handover',
                'movement_date'   => $request->movement_date,
                'device_condition'=> $request->device_condition ?? 'ممتاز',
                'accessories'     => $request->accessories ?? [],
                'financial_clearance' => 'cleared',
                'received_by_name'=> $request->received_by_name,
                'notes'           => $request->notes,
                'processed_by'    => $user?->id,
                'status'          => 'active',
            ]);

            // تحديث حالة الماكينة
            $machine->update([
                'custody_status'    => 'in_custody',
                'current_agent_id'  => $request->branch_agent_id,
                'current_custody_id'=> $movement->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم تسليم العهدة للوكيل بنجاح وإنشاء محضر التسليم',
            'data'    => $machine->fresh()->load(['custodyMovements.agent', 'currentAgent']),
        ], 201);
    }

    /**
     * POST /api/pos-machines/{id}/return-custody
     * استرجاع عهدة الجهاز للشركة مع إنشاء محضر الإرجاع
     */
    public function returnCustody(Request $request, $id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باسترجاع عهدة ماكينات POS'], 403);
        }

        $machine = PosMachine::findOrFail($id);

        if (($machine->custody_status ?? 'available') !== 'in_custody') {
            return response()->json([
                'success' => false,
                'message' => 'هذا الجهاز ليس بعهدة أي وكيل حالياً.',
            ], 422);
        }

        $request->validate([
            'movement_date'       => 'required|date',
            'device_condition'    => 'nullable|string',
            'accessories'         => 'nullable|array',
            'return_reason'       => 'nullable|string',
            'financial_clearance' => 'nullable|in:cleared,pending_audit,has_deductions',
            'received_by_name'    => 'nullable|string',
            'notes'               => 'nullable|string',
            'new_status'          => 'nullable|in:available,maintenance,damaged',
        ]);

        $user = $this->resolveUser();

        DB::transaction(function () use ($request, $machine, $user) {
            // إغلاق محضر التسليم النشط
            if ($machine->current_custody_id) {
                PosCustodyMovement::where('id', $machine->current_custody_id)
                    ->update(['status' => 'closed']);
            }

            // إنشاء محضر الإرجاع
            PosCustodyMovement::create([
                'reference_no'    => PosCustodyMovement::generateReferenceNo('return'),
                'pos_machine_id'  => $machine->id,
                'branch_agent_id' => $machine->current_agent_id,
                'movement_type'   => 'return',
                'movement_date'   => $request->movement_date,
                'device_condition'=> $request->device_condition ?? 'ممتاز',
                'accessories'     => $request->accessories ?? [],
                'return_reason'   => $request->return_reason,
                'financial_clearance' => $request->financial_clearance ?? 'cleared',
                'received_by_name'=> $request->received_by_name,
                'notes'           => $request->notes,
                'processed_by'    => $user?->id,
                'status'          => 'closed',
            ]);

            // تحديث حالة الماكينة
            $machine->update([
                'custody_status'    => $request->new_status ?? 'available',
                'current_agent_id'  => null,
                'current_custody_id'=> null,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'تم استرجاع العهدة للشركة بنجاح وإنشاء محضر الإرجاع',
            'data'    => $machine->fresh()->load(['custodyMovements.agent', 'currentAgent']),
        ]);
    }

    /**
     * GET /api/pos-machines/{id}/custody-history
     * جلب السجل التاريخي الكامل لحركة عهدة الجهاز (Timeline)
     */
    public function custodyHistory($id)
    {
        if (!$this->hasPosAccess()) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك'], 403);
        }

        $machine = PosMachine::with([
            'currentAgent',
            'custodyMovements' => function ($q) {
                $q->with(['agent', 'processor'])
                  ->orderBy('movement_date', 'asc')
                  ->orderBy('id', 'asc');
            },
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'machine'          => $machine,
                'custody_movements'=> $machine->custodyMovements,
            ],
        ]);
    }
}
