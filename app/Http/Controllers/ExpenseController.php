<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('recipient', 'like', "%{$search}%")
                  ->orWhere('voucher_number', 'like', "%{$search}%");
            });
        }

        if ($request->has('category') && $request->category != '') {
            $query->where('category', $request->category);
        }

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->get();

        // Calculate statistics (for current month)
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();
        
        $monthlyTotal = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->sum('amount');
        $monthlyCount = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])->count();

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'statistics' => [
                'monthly_total' => (float)$monthlyTotal,
                'monthly_count' => $monthlyCount,
            ]
        ]);
    }

    public function show($id)
    {
        $expense = Expense::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $expense
        ]);
    }

    public function store(Request $request)
    {
        if ($request->has('items') && is_string($request->items)) {
            $request->merge([
                'items' => json_decode($request->items, true)
            ]);
        }

        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'voucher_number' => 'nullable|string',
            'expense_type' => 'required|string',
            'expense_date' => 'required|date',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'is_indemnity' => 'nullable|boolean',
            'payment_source' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('expense_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        DB::beginTransaction();
        try {
            $expense = Expense::create($data);

            // Process financial transaction (balance check and insert)
            $this->processFinancialTransaction($expense);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة المصروف بنجاح',
                'data' => $expense
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if ($request->has('items') && is_string($request->items)) {
            $request->merge([
                'items' => json_decode($request->items, true)
            ]);
        }

        $request->validate([
            'name' => 'required|string',
            'recipient' => 'nullable|string',
            'category' => 'required|string',
            'sub_category' => 'nullable|string',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'voucher_number' => 'nullable|string',
            'expense_type' => 'required|string',
            'expense_date' => 'required|date',
            'status' => 'required|string',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'receipt_image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240',
            'is_indemnity' => 'nullable|boolean',
            'payment_source' => 'nullable|string',
        ]);

        $data = $request->all();

        if ($request->hasFile('receipt_image')) {
            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            $path = $request->file('receipt_image')->store('expense_receipts', 'public');
            $data['receipt_image'] = $path;
        }

        DB::beginTransaction();
        try {
            $expense->update($data);

            // Process financial transaction (delete old and insert/check new)
            $this->processFinancialTransaction($expense);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث المصروف بنجاح',
                'data' => $expense
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function destroy($id)
    {
        $expense = Expense::findOrFail($id);
        
        DB::beginTransaction();
        try {
            // Delete associated transactions first
            \App\Models\TreasuryTransaction::where('description', 'like', "مصروف رقم: {$expense->id}%")->delete();
            \App\Models\BankTransaction::where('notes', 'like', "مصروف رقم: {$expense->id}%")->delete();

            if ($expense->receipt_image) {
                Storage::disk('public')->delete($expense->receipt_image);
            }
            
            $expense->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف المصروف بنجاح'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل حذف المصروف: ' . $e->getMessage()
            ], 422);
        }
    }

    private function processFinancialTransaction(Expense $expense)
    {
        // 1. Delete any existing transactions matching this expense
        \App\Models\TreasuryTransaction::where('description', 'like', "مصروف رقم: {$expense->id}%")->delete();
        \App\Models\BankTransaction::where('notes', 'like', "مصروف رقم: {$expense->id}%")->delete();

        // 2. Only process if not indemnity and status is "مدفوع"
        if ($expense->is_indemnity || $expense->status !== 'مدفوع') {
            return;
        }

        $source = $expense->payment_source ?: 'treasury';
        $amount = (float) $expense->amount;

        if ($source === 'treasury') {
            // Calculate treasury balance
            $totalIncome = \App\Models\TreasuryTransaction::where('type', 'income')->sum('amount');
            $totalExpense = \App\Models\TreasuryTransaction::where('type', 'expense')->sum('amount');
            $currentBalance = $totalIncome - $totalExpense;

            if ($currentBalance < $amount) {
                throw new \Exception("الرصيد في الخزينة غير كافٍ لتغطية هذا المصروف. الرصيد الحالي: " . number_format($currentBalance, 2) . " د.ل");
            }

            // Create treasury transaction
            \App\Models\TreasuryTransaction::create([
                'transaction_date' => $expense->expense_date,
                'type' => 'expense',
                'amount' => $amount,
                'description' => "مصروف رقم: {$expense->id} - {$expense->name}",
                'source' => $expense->recipient,
                'notes' => $expense->notes,
                'payment_source' => 'treasury',
            ]);
        } else {
            // Calculate bank balance
            $totalDeposits = \App\Models\BankTransaction::where('bank_name', $source)->where('type', 'deposit')->sum('amount');
            $totalWithdrawals = \App\Models\BankTransaction::where('bank_name', $source)->where('type', 'withdrawal')->sum('amount');
            $currentBalance = $totalDeposits - $totalWithdrawals;

            if ($currentBalance < $amount) {
                throw new \Exception("الرصيد في {$source} غير كافٍ لتغطية هذا المصروف. الرصيد الحالي: " . number_format($currentBalance, 2) . " د.ل");
            }

            // Get bank account number
            $bank = \App\Models\Bank::where('name', $source)->first();
            $accountNumber = $bank ? $bank->account_number : null;

            // Create bank transaction
            \App\Models\BankTransaction::create([
                'transaction_date' => $expense->expense_date,
                'bank_name' => $source,
                'account_number' => $accountNumber,
                'amount' => $amount,
                'type' => 'withdrawal',
                'reconciled' => true,
                'notes' => "مصروف رقم: {$expense->id} - {$expense->name}",
                'transaction_type' => 'مصروفات تشغيلية',
            ]);
        }
    }
}
