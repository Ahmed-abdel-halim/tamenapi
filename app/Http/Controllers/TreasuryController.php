<?php

namespace App\Http\Controllers;

use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TreasuryController extends Controller
{
    public function index(Request $request)
    {
        $query = TreasuryTransaction::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('source', 'like', "%{$search}%")
                  ->orWhere('supplier_phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $transactions = $query->orderBy('transaction_date', 'desc')->get();

        // إحصائيات
        $totalIncome  = TreasuryTransaction::where('type', 'income')->sum('amount');
        $totalExpense = TreasuryTransaction::where('type', 'expense')->sum('amount');
        $balance      = $totalIncome - $totalExpense;

        // إحصائيات الشهر الحالي
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $monthIncome  = TreasuryTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        $monthExpense = TreasuryTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return response()->json([
            'success' => true,
            'data'    => $transactions,
            'stats'   => [
                'total_income'   => (float) $totalIncome,
                'total_expense'  => (float) $totalExpense,
                'balance'        => (float) $balance,
                'month_income'   => (float) $monthIncome,
                'month_expense'  => (float) $monthExpense,
                'month_net'      => (float) ($monthIncome - $monthExpense),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'transaction_date'    => 'required|date',
            'type'                => 'required|in:income,expense',
            'amount'              => 'required|numeric|min:0.01',
            'description'         => 'required|string',
            'supplier_phone'      => 'nullable|string',
            'source'              => 'nullable|string',
            'reference_number'    => 'nullable|string',
            'branch_agent_id'     => 'nullable|integer',
            'expense_destination' => 'nullable|string',
            'payment_source'      => 'nullable|string',
            'notes'               => 'nullable|string',
            'voucher_image'       => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:10240',
        ]);

        $data = $request->except('voucher_image');

        if ($request->hasFile('voucher_image')) {
            $path = $request->file('voucher_image')->store('treasury_vouchers', 'public');
            $data['voucher_image'] = $path;
        }

        $transaction = TreasuryTransaction::create($data);

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة المعاملة بنجاح',
            'data'    => $transaction,
        ], 201);
    }

    public function show($id)
    {
        $transaction = TreasuryTransaction::findOrFail($id);
        return response()->json(['success' => true, 'data' => $transaction]);
    }

    public function update(Request $request, $id)
    {
        $transaction = TreasuryTransaction::findOrFail($id);

        $request->validate([
            'transaction_date' => 'required|date',
            'type'             => 'required|in:income,expense',
            'amount'           => 'required|numeric|min:0.01',
            'description'      => 'required|string',
        ]);

        $data = $request->except('voucher_image');

        if ($request->hasFile('voucher_image')) {
            if ($transaction->voucher_image) {
                Storage::disk('public')->delete($transaction->voucher_image);
            }
            $path = $request->file('voucher_image')->store('treasury_vouchers', 'public');
            $data['voucher_image'] = $path;
        }

        $transaction->update($data);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث المعاملة بنجاح',
            'data'    => $transaction,
        ]);
    }

    public function destroy($id)
    {
        $transaction = TreasuryTransaction::findOrFail($id);

        if ($transaction->voucher_image) {
            Storage::disk('public')->delete($transaction->voucher_image);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المعاملة بنجاح',
        ]);
    }

    public function balance()
    {
        $totalIncome  = TreasuryTransaction::where('type', 'income')->sum('amount');
        $totalExpense = TreasuryTransaction::where('type', 'expense')->sum('amount');

        return response()->json([
            'success'       => true,
            'total_income'  => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'balance'       => (float) ($totalIncome - $totalExpense),
        ]);
    }

    public function dailyReport(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $income  = TreasuryTransaction::where('type', 'income')
            ->whereDate('transaction_date', $date)->get();
        $expense = TreasuryTransaction::where('type', 'expense')
            ->whereDate('transaction_date', $date)->get();

        return response()->json([
            'success'        => true,
            'date'           => $date,
            'income'         => $income,
            'expense'        => $expense,
            'total_income'   => $income->sum('amount'),
            'total_expense'  => $expense->sum('amount'),
            'net'            => $income->sum('amount') - $expense->sum('amount'),
        ]);
    }
}
