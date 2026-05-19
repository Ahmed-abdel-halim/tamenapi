<?php

namespace App\Http\Controllers;

use App\Models\PosMachine;
use App\Models\PosTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PosMachineController extends Controller
{
    // ─── ماكينات POS ────────────────────────────────────────────────────────────

    public function index()
    {
        $machines = PosMachine::withCount('transactions')
            ->withSum('transactions', 'amount')
            ->orderBy('machine_name')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $machines,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_name'   => 'required|string',
            'machine_serial' => 'nullable|string',
            'bank_name'      => 'required|string',
            'merchant_id'    => 'nullable|string',
            'location'       => 'nullable|string',
            'is_active'      => 'nullable|boolean',
            'notes'          => 'nullable|string',
        ]);

        $machine = PosMachine::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الماكينة بنجاح',
            'data'    => $machine,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $machine = PosMachine::findOrFail($id);

        $request->validate([
            'machine_name' => 'required|string',
            'bank_name'    => 'required|string',
        ]);

        $machine->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الماكينة بنجاح',
            'data'    => $machine,
        ]);
    }

    public function destroy($id)
    {
        PosMachine::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'تم حذف الماكينة']);
    }

    public function toggleActive($id)
    {
        $machine = PosMachine::findOrFail($id);
        $machine->update(['is_active' => !$machine->is_active]);
        return response()->json(['success' => true, 'data' => $machine]);
    }

    // ─── معاملات POS ────────────────────────────────────────────────────────────

    public function transactions(Request $request)
    {
        $query = PosTransaction::with('machine');

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
        $monthAmount  = PosTransaction::whereBetween('transaction_date', [$startOfMonth, $endOfMonth])->sum('amount');

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
        $request->validate([
            'pos_machine_id'     => 'required|exists:pos_machines,id',
            'transaction_date'   => 'required|date',
            'amount'             => 'required|numeric|min:0.01',
            'transactions_count' => 'nullable|integer|min:1',
            'reference_number'   => 'nullable|string',
            'notes'              => 'nullable|string',
            'report_file'        => 'nullable|file|mimes:pdf,xlsx,xls,csv|max:20480',
        ]);

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
        $transaction = PosTransaction::findOrFail($id);

        $request->validate([
            'pos_machine_id'   => 'required|exists:pos_machines,id',
            'transaction_date' => 'required|date',
            'amount'           => 'required|numeric|min:0.01',
        ]);

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
        $transaction = PosTransaction::findOrFail($id);

        if ($transaction->report_file) {
            Storage::disk('public')->delete($transaction->report_file);
        }

        $transaction->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف المعاملة']);
    }

    public function toggleReconcile($id)
    {
        $transaction = PosTransaction::findOrFail($id);
        $transaction->update(['is_reconciled' => !$transaction->is_reconciled]);
        return response()->json(['success' => true, 'data' => $transaction]);
    }

    // ─── لوحة قيادة POS ─────────────────────────────────────────────────────────

    public function dashboard()
    {
        $machines = PosMachine::where('is_active', true)->get();

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

        $grandTotal     = PosTransaction::sum('amount');
        $monthGrandTotal = PosTransaction::whereBetween(
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
}
