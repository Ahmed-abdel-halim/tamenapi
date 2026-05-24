<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\TreasuryTransaction;
use App\Models\BankTransaction;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseDeductionTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create an admin user for DELETE requests
        $this->admin = User::create([
            'username' => 'admin',
            'name' => 'Admin User',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);
    }

    public function test_insufficient_treasury_balance_fails_saving_expense()
    {
        $response = $this->postJson('/api/expenses', [
            'name' => 'ورق طباعة',
            'recipient' => 'أحمد',
            'category' => 'قرطاسية',
            'amount' => 100,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'treasury',
            'is_indemnity' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $this->assertStringContainsString('الرصيد في الخزينة غير كافٍ', $response->json('message'));
        $this->assertEquals(0, Expense::count());
    }

    public function test_sufficient_treasury_balance_creates_expense_and_deducts()
    {
        // 1. Add income to treasury
        TreasuryTransaction::create([
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'amount' => 500,
            'description' => 'إيداع نقدي',
        ]);

        // 2. Add expense
        $response = $this->postJson('/api/expenses', [
            'name' => 'ورق طباعة',
            'recipient' => 'أحمد',
            'category' => 'قرطاسية',
            'amount' => 100,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'treasury',
            'is_indemnity' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, Expense::count());
        
        $expense = Expense::first();

        // 3. Verify treasury transaction was created
        $this->assertDatabaseHas('treasury_transactions', [
            'type' => 'expense',
            'amount' => 100,
            'description' => "مصروف رقم: {$expense->id} - ورق طباعة",
        ]);

        // 4. Verify balance is correct
        $totalIncome = TreasuryTransaction::where('type', 'income')->sum('amount');
        $totalExpense = TreasuryTransaction::where('type', 'expense')->sum('amount');
        $this->assertEquals(400, $totalIncome - $totalExpense);
    }

    public function test_insufficient_bank_balance_fails_saving_expense()
    {
        $bank = Bank::firstOrCreate(['name' => 'مصرف الأمان']);
        $bank->account_number = '123456';
        $bank->save();

        $response = $this->postJson('/api/expenses', [
            'name' => 'صيانة مكيف',
            'recipient' => 'شركة التبريد',
            'category' => 'صيانة',
            'amount' => 300,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'مصرف الأمان',
            'is_indemnity' => false,
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('الرصيد في مصرف الأمان غير كافٍ', $response->json('message'));
        $this->assertEquals(0, Expense::count());
    }

    public function test_sufficient_bank_balance_creates_expense_and_deducts()
    {
        $bank = Bank::firstOrCreate(['name' => 'مصرف الأمان']);
        $bank->account_number = '123456';
        $bank->save();

        // Deposit money to bank
        BankTransaction::create([
            'transaction_date' => now()->toDateString(),
            'bank_name' => 'مصرف الأمان',
            'account_number' => '123456',
            'amount' => 1000,
            'type' => 'deposit',
        ]);

        $response = $this->postJson('/api/expenses', [
            'name' => 'صيانة مكيف',
            'recipient' => 'شركة التبريد',
            'category' => 'صيانة',
            'amount' => 300,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'مصرف الأمان',
            'is_indemnity' => false,
        ]);

        $response->assertStatus(201);
        $this->assertEquals(1, Expense::count());
        
        $expense = Expense::first();

        // Verify bank transaction was created
        $this->assertDatabaseHas('bank_transactions', [
            'bank_name' => 'مصرف الأمان',
            'account_number' => '123456',
            'amount' => 300,
            'type' => 'withdrawal',
            'notes' => "مصروف رقم: {$expense->id} - صيانة مكيف",
        ]);

        // Verify balance
        $totalDeposits = BankTransaction::where('bank_name', 'مصرف الأمان')->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = BankTransaction::where('bank_name', 'مصرف الأمان')->where('type', 'withdrawal')->sum('amount');
        $this->assertEquals(700, $totalDeposits - $totalWithdrawals);
    }

    public function test_updating_expense_to_unpaid_removes_financial_transaction()
    {
        // 1. Treasury setup with balance
        TreasuryTransaction::create([
            'transaction_date' => now()->toDateString(),
            'type' => 'income',
            'amount' => 500,
            'description' => 'إيداع نقدي',
        ]);

        // 2. Create paid expense
        $expense = Expense::create([
            'name' => 'ورق طباعة',
            'recipient' => 'أحمد',
            'category' => 'قرطاسية',
            'amount' => 100,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'treasury',
            'is_indemnity' => false,
        ]);

        // Directly link treasury transaction
        TreasuryTransaction::create([
            'transaction_date' => now()->toDateString(),
            'type' => 'expense',
            'amount' => 100,
            'description' => "مصروف رقم: {$expense->id} - ورق طباعة",
            'payment_source' => 'treasury',
        ]);

        // Verify it exists
        $this->assertEquals(1, TreasuryTransaction::where('type', 'expense')->count());

        // 3. Update expense to 'معلق'
        $response = $this->putJson("/api/expenses/{$expense->id}", [
            'name' => 'ورق طباعة',
            'recipient' => 'أحمد',
            'category' => 'قرطاسية',
            'amount' => 100,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'معلق',
            'payment_source' => 'treasury',
            'is_indemnity' => false,
        ]);

        $response->assertStatus(200);

        // 4. Verify transaction was deleted
        $this->assertEquals(0, TreasuryTransaction::where('type', 'expense')->count());
    }

    public function test_deleting_expense_removes_financial_transaction()
    {
        // 1. Create paid expense
        $expense = Expense::create([
            'name' => 'ورق طباعة',
            'recipient' => 'أحمد',
            'category' => 'قرطاسية',
            'amount' => 100,
            'currency' => 'LYD',
            'expense_type' => 'حوالة مصرفية',
            'expense_date' => now()->toDateString(),
            'status' => 'مدفوع',
            'payment_source' => 'treasury',
            'is_indemnity' => false,
        ]);

        // Directly link treasury transaction
        TreasuryTransaction::create([
            'transaction_date' => now()->toDateString(),
            'type' => 'expense',
            'amount' => 100,
            'description' => "مصروف رقم: {$expense->id} - ورق طباعة",
            'payment_source' => 'treasury',
        ]);

        $this->assertEquals(1, TreasuryTransaction::where('type', 'expense')->count());

        // 2. Delete expense with Admin headers
        $response = $this->deleteJson("/api/expenses/{$expense->id}", [], [
            'X-User-Id' => $this->admin->id
        ]);
        $response->assertStatus(200);

        // 3. Verify transaction and expense are deleted
        $this->assertEquals(0, Expense::count());
        $this->assertEquals(0, TreasuryTransaction::where('type', 'expense')->count());
    }
}
