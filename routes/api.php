<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchAgentController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\PlateController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\InsuranceDocumentController;
use App\Http\Controllers\InternationalInsuranceDocumentController;
use App\Http\Controllers\TravelInsuranceDocumentController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\FinancialArchiveController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\BankTransactionController;
use App\Http\Controllers\ResidentInsuranceDocumentController;
use App\Http\Controllers\MarineStructureInsuranceDocumentController;
use App\Http\Controllers\MarineEngineModelController;
use App\Http\Controllers\ProfessionalLiabilityInsuranceDocumentController;
use App\Http\Controllers\PersonalAccidentInsuranceDocumentController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\FinancialStatisticsController;
use App\Http\Controllers\EmployeePayrollController;
use App\Http\Controllers\ClaimController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Http\Controllers\AgencyCancellationController;
use App\Http\Controllers\CompanyDocumentController;
use App\Http\Controllers\RentalVoucherController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseSubCategoryController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/public/agent-register', [BranchAgentController::class, 'publicRegister']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $user = User::where('username', $request->username)->first();
    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
    }

    if (isset($user->is_active) && $user->is_active === false) {
        return response()->json(['message' => 'هذا الحساب غير نشط حالياً، يرجى مراجعة الإدارة'], 403);
    }

    // جلب معلومات الوكيل/الفرع المرتبط بالمستخدم (إذا كان موجوداً)
    $branchAgent = $user->branchAgent;
    $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);

    // إنشاء توكن Sanctum
    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'is_admin' => $user->is_admin ?? false,
            'authorized_documents' => $authorizedDocuments,
            'branch_agent_id' => $branchAgent ? $branchAgent->id : null,
            'is_blocked' => $user->is_blocked ?? false,
        ],
        'token' => $token,
    ]);
});

// Endpoint لتحديث بيانات المستخدم الحالي (بعد تحديث الصلاحيات)
Route::get('/user/{id}/refresh', function (Request $request, $id) {
    try {
        $user = User::findOrFail($id);

        // جلب معلومات الوكيل/الفرع المرتبط بالمستخدم (إذا كان موجوداً)
        $branchAgent = $user->branchAgent;
        $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'is_admin' => $user->is_admin ?? false,
                'authorized_documents' => $authorizedDocuments,
                'is_blocked' => $user->is_blocked ?? false,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'المستخدم غير موجود',
            'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
        ], 404);
    }
});

use App\Http\Controllers\EmployeeRequestController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications/unread-count', [\App\Http\Controllers\NotificationController::class, 'unreadCount']);
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);

    Route::post('/users/{user}/employee-files', [UserController::class, 'uploadEmployeeFile']);
    Route::get('/profile-update-requests', [\App\Http\Controllers\ProfileUpdateRequestController::class, 'index']);
    Route::post('/profile-update-requests', [\App\Http\Controllers\ProfileUpdateRequestController::class, 'submit']);
    Route::get('/profile-update-requests/current', [\App\Http\Controllers\ProfileUpdateRequestController::class, 'currentStatus']);
    Route::post('/profile-update-requests/{id}/approve', [\App\Http\Controllers\ProfileUpdateRequestController::class, 'approve']);
    Route::post('/profile-update-requests/{id}/reject', [\App\Http\Controllers\ProfileUpdateRequestController::class, 'reject']);
    Route::apiResource('users', UserController::class);
    Route::put('/users/{user}/eidc-credentials', [UserController::class, 'updateEidcCredentials']);
    Route::put('/users/{user}/lifo-credentials', [UserController::class, 'updateLifoCredentials']);
    Route::post('/users/{id}/toggle-landing', [UserController::class, 'toggleShowOnLanding']);
    Route::get('/users/{user}/salary-history', [UserController::class, 'salaryHistory']);
    Route::apiResource('employee-requests', EmployeeRequestController::class);
    Route::apiResource('agent-requests', \App\Http\Controllers\AgentRequestController::class);
    Route::apiResource('agency-cancellations', AgencyCancellationController::class);
    Route::apiResource('agent-transfers', \App\Http\Controllers\AgentTransferController::class);

    Route::get('/employee-payrolls/employees', [EmployeePayrollController::class, 'employees']);
    Route::get('/employee-payrolls/reports', [EmployeePayrollController::class, 'taxSSReport']);
    Route::get('/employee-payrolls', [EmployeePayrollController::class, 'index']);
    Route::post('/employee-payrolls/bulk-pay', [EmployeePayrollController::class, 'bulkPay']);
    Route::post('/employee-payrolls', [EmployeePayrollController::class, 'upsert']);
});

// Endpoint لتحديث authorized_documents في users من branches_agents
Route::post('/sync-user-permissions', function (Request $request) {
    try {
        $branchAgents = \App\Models\BranchAgent::whereNotNull('user_id')->get();
        $updated = 0;

        foreach ($branchAgents as $agent) {
            if ($agent->user_id && $agent->authorized_documents) {
                $user = \App\Models\User::find($agent->user_id);
                if ($user) {
                    $user->authorized_documents = $agent->authorized_documents;
                    $user->save();
                    $updated++;
                }
            }
        }

        return response()->json([
            'message' => "تم تحديث $updated مستخدم بنجاح",
            'updated_count' => $updated,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'حدث خطأ',
            'error' => $e->getMessage(),
        ], 500);
    }
});
Route::get('/public/employees', [UserController::class, 'publicEmployees']);
Route::put('/users/{user}/email', [UserController::class, 'updateEmail']);
Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
Route::get('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'getMonthlyAccountClosure']);
Route::post('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'saveMonthlyAccountClosure']);
Route::get('/branches-agents/{id}/monthly-account-closure-print', [BranchAgentController::class, 'printMonthlyAccountClosure']);
Route::get('/branches-agents/monthly-account-closures-report', [BranchAgentController::class, 'getMonthlyAccountClosuresReport']);
Route::apiResource('branches-agents', BranchAgentController::class);
Route::apiResource('payment-vouchers', 'App\Http\Controllers\PaymentVoucherController');
Route::apiResource('expenses', 'App\Http\Controllers\ExpenseController');
Route::apiResource('expense-categories', ExpenseCategoryController::class);
Route::get('/expense-subcategories', [ExpenseSubCategoryController::class, 'index']);
Route::post('/expense-subcategories', [ExpenseSubCategoryController::class, 'store']);
Route::delete('/expense-subcategories/{id}', [ExpenseSubCategoryController::class, 'destroy']);

Route::get('/reset-categories', function () {
    \Illuminate\Support\Facades\DB::table('expense_categories')->delete();
    $cats = [
        'قرطاسية',
        'صيانة',
        'خدمات',
        'إيجار',
        'ضيافة',
        'التعويضات',
        'قرطاسيه مكتبيه مستهلكه',
        'مصاريف (رصيد واشتراكات حكوميه (كهرباء -انترنت -رصيد اتصالات-ماء -صرف صحي ))',
        'مصاريف مواد تنظيف',
        'عهده ماليه خاصه بالموظفين',
        'صيانة (الكترونيات - المبنى - الاثاث -الخ )',
        'صيانه السيارات الخاصه بالموظفين والخدمات',
        'الكترونيات ثابته',
        'دعايه واعلان وهدايا مستهلكه (( خاص بالوكلاء ))',
        'رسوم ومصاريف اشتراكات المعارض والاجتماعات الخاصه بالشركه',
        'رسوم اصدار وتجديد غرفه التجاره والصناعه والزراعه',
        'قرطاسيه مكتبيه ثابته',
        'رسوم اشتراكات اعاده التامين',
        'مصلحة الضرائب والميزانيات'
    ];
    $insertData = [];
    foreach ($cats as $cat) {
        $insertData[] = ['name' => $cat, 'created_at' => now(), 'updated_at' => now()];
    }
    \Illuminate\Support\Facades\DB::table('expense_categories')->insert($insertData);
    return 'تم تنظيف الفئات بنجاح! جميع الفئات الوهمية تم مسحها، وتمت إضافة الفئات الرسمية فقط. يمكنك العودة للنظام الآن وتحديث الصفحة.';
});

Route::apiResource('school-student-insurance', 'App\Http\Controllers\SchoolStudentInsuranceDocumentController');
Route::get('/school-student-insurance/{id}/print', ['App\Http\Controllers\SchoolStudentInsuranceDocumentController', 'print']);
Route::apiResource('cash-in-transit-insurance', 'App\Http\Controllers\CashInTransitInsuranceDocumentController');
Route::get('/cash-in-transit-insurance/{id}/print', ['App\Http\Controllers\CashInTransitInsuranceDocumentController', 'print']);
Route::apiResource('cargo-insurance', 'App\Http\Controllers\CargoInsuranceDocumentController');
Route::get('/cargo-insurance/{id}/print', ['App\Http\Controllers\CargoInsuranceDocumentController', 'print']);
Route::get('/branches-agents/{id}/print', [BranchAgentController::class, 'print']);
Route::get('/branches-agents/{id}/account-report', [BranchAgentController::class, 'accountReport']);
Route::get('/branches-agents/{id}/revenue-report', [BranchAgentController::class, 'revenueReport']);
Route::post('/branches-agents/{id}/toggle-block', [BranchAgentController::class, 'toggleBlock']);
Route::post('/branches-agents/{id}/toggle-landing', [BranchAgentController::class, 'toggleShowOnLanding']);
Route::post('/branches-agents/{id}/approve', [BranchAgentController::class, 'approveAgent']);
Route::get('/reports/outstanding-debts', [\App\Http\Controllers\DebtReportController::class, 'getOutstandingDebts']);

// Financial Management Routes
Route::apiResource('commissions', \App\Http\Controllers\CommissionController::class);
Route::post('/commissions/{id}/pay', [\App\Http\Controllers\CommissionController::class, 'markAsPaid']);
Route::apiResource('bank-transactions', \App\Http\Controllers\BankTransactionController::class);
Route::post('/bank-transactions/{id}/reconcile', [\App\Http\Controllers\BankTransactionController::class, 'toggleReconcile']);

// Bank Settings (Dynamic Banks and Transaction Types)
Route::get('/bank-settings/banks', [\App\Http\Controllers\BankSettingsController::class, 'getBanks']);
Route::post('/bank-settings/banks', [\App\Http\Controllers\BankSettingsController::class, 'storeBank']);
Route::put('/bank-settings/banks/{id}', [\App\Http\Controllers\BankSettingsController::class, 'updateBank']);
Route::delete('/bank-settings/banks/{id}', [\App\Http\Controllers\BankSettingsController::class, 'deleteBank']);
Route::get('/bank-settings/source-banks', [\App\Http\Controllers\BankSettingsController::class, 'getSourceBanks']);
Route::post('/bank-settings/source-banks', [\App\Http\Controllers\BankSettingsController::class, 'storeSourceBank']);
Route::put('/bank-settings/source-banks/{id}', [\App\Http\Controllers\BankSettingsController::class, 'updateSourceBank']);
Route::delete('/bank-settings/source-banks/{id}', [\App\Http\Controllers\BankSettingsController::class, 'deleteSourceBank']);
Route::get('/bank-settings/transaction-types', [\App\Http\Controllers\BankSettingsController::class, 'getTransactionTypes']);
Route::post('/bank-settings/transaction-types', [\App\Http\Controllers\BankSettingsController::class, 'storeTransactionType']);
Route::delete('/bank-settings/transaction-types/{id}', [\App\Http\Controllers\BankSettingsController::class, 'deleteTransactionType']);
Route::apiResource('financial-archive', \App\Http\Controllers\FinancialArchiveController::class);
Route::get('/dashboard/statistics', [BranchAgentController::class, 'getStatistics']);
Route::get('/dashboard/latest-documents', [BranchAgentController::class, 'getLatestDocuments']);
Route::get('/union-balances', [App\Http\Controllers\UnionBalancePurchaseController::class, 'index']);
Route::post('/union-balances', [App\Http\Controllers\UnionBalancePurchaseController::class, 'store']);
Route::put('/union-balances/{id}', [App\Http\Controllers\UnionBalancePurchaseController::class, 'update']);
Route::delete('/union-balances/{id}', [App\Http\Controllers\UnionBalancePurchaseController::class, 'destroy']);
Route::get('/financial-statistics', [FinancialStatisticsController::class, 'getStatistics']);
Route::get('/financial-statistics/all-agents-revenue', [FinancialStatisticsController::class, 'getAllAgentsRevenue']);
Route::apiResource('cities', CityController::class);
Route::apiResource('plates', PlateController::class);
Route::apiResource('vehicle-types', VehicleTypeController::class);
Route::apiResource('colors', ColorController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('insurance-documents', InsuranceDocumentController::class);
Route::get('/insurance-documents/{id}/print', [InsuranceDocumentController::class, 'print']);
Route::post('/insurance-documents/{id}/transfer-ownership', [InsuranceDocumentController::class, 'transferOwnership']);
Route::get('/insurance-documents/{id}/ownership-transfer-history', [InsuranceDocumentController::class, 'getOwnershipTransferHistory']);

// ─── EIDC Authority Integration Routes (تأمين إجباري سيارات) ─────────────────
Route::get('/insurance-documents/eidc/vehicle-types', [InsuranceDocumentController::class, 'eidcVehicleTypes']);
Route::get('/insurance-documents/eidc/vehicle-specs', [InsuranceDocumentController::class, 'eidcVehicleSpecs']);
Route::get('/insurance-documents/eidc/vehicle-details', [InsuranceDocumentController::class, 'eidcVehicleDetails']);
Route::post('/insurance-documents/eidc/inquiry', [InsuranceDocumentController::class, 'eidcInquiry']);
Route::get('/insurance-documents/eidc/serial-stats', [InsuranceDocumentController::class, 'eidcSerialStats']);
Route::post('/insurance-documents/{id}/eidc-cancel', [InsuranceDocumentController::class, 'eidcCancel']);
Route::post('/insurance-documents/{id}/eidc-retry', [InsuranceDocumentController::class, 'eidcRetrySync']);
Route::get('/insurance-documents/{id}/eidc-print', [InsuranceDocumentController::class, 'eidcPrintProxy']);
Route::post('/insurance-documents/eidc-sync-all', [InsuranceDocumentController::class, 'eidcSyncFromAuthority']);


Route::apiResource('international-insurance-documents', InternationalInsuranceDocumentController::class);
Route::get('/international-insurance-documents/{id}/print', [InternationalInsuranceDocumentController::class, 'print']);

Route::apiResource('travel-insurance-documents', TravelInsuranceDocumentController::class);
Route::get('/travel-insurance-documents/{id}/print', [TravelInsuranceDocumentController::class, 'print']);

Route::apiResource('resident-insurance-documents', ResidentInsuranceDocumentController::class);
Route::get('/resident-insurance-documents/{id}/print', [ResidentInsuranceDocumentController::class, 'print']);

Route::get('/marine-engine-models', [MarineEngineModelController::class, 'index']);
Route::post('/marine-engine-models', [MarineEngineModelController::class, 'store']);

Route::apiResource('marine-structure-insurance-documents', MarineStructureInsuranceDocumentController::class)->parameters([
    'marine-structure-insurance-documents' => 'document'
]);
Route::get('/marine-structure-insurance-documents/{document}/print', [MarineStructureInsuranceDocumentController::class, 'print']);

Route::apiResource('professional-liability-insurance-documents', ProfessionalLiabilityInsuranceDocumentController::class)->parameters([
    'professional-liability-insurance-documents' => 'document'
]);
Route::get('/professional-liability-insurance-documents/{document}/print', [ProfessionalLiabilityInsuranceDocumentController::class, 'print']);

// Routes for professions management
Route::get('/professions', [ProfessionController::class, 'index']);
Route::post('/professions', [ProfessionController::class, 'store']);
Route::delete('/professions/{id}', [ProfessionController::class, 'destroy']);

use App\Http\Controllers\DocumentRequestController;
Route::apiResource('document-requests', DocumentRequestController::class);

Route::apiResource('personal-accident-insurance-documents', PersonalAccidentInsuranceDocumentController::class)->parameters([
    'personal-accident-insurance-documents' => 'document'
]);
Route::get('/personal-accident-insurance-documents/{document}/print', [PersonalAccidentInsuranceDocumentController::class, 'print']);

Route::apiResource('external-entities', \App\Http\Controllers\ExternalEntityController::class);
Route::apiResource('mail-documents', \App\Http\Controllers\MailDocumentController::class);
Route::apiResource('company-documents', CompanyDocumentController::class);
Route::apiResource('rental-vouchers', RentalVoucherController::class);

Route::get('/fix-storage', function () {
    try {
        if (is_link(public_path('storage'))) {
            app()->make('files')->delete(public_path('storage'));
        }
        \Illuminate\Support\Facades\Artisan::call('storage:link');
        return "Storage link fixed successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/run-migrations', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return "Migrations ran successfully! Output: " . \Illuminate\Support\Facades\Artisan::output();
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

Route::get('/claims/document-info', [ClaimController::class, 'fetchDocumentInfo']);
Route::get('/claims/search-documents', [ClaimController::class, 'searchDocuments']);
Route::apiResource('claims', ClaimController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
Route::post('/claims/{id}/transfers', [ClaimController::class, 'addTransfer']);

// ─── Excel Import Routes (استيراد ملفات Excel) ──────────────────────────────
Route::post('/excel-import/analyze', [\App\Http\Controllers\ExcelImportController::class, 'analyzeFile']);
Route::post('/excel-import/confirm', [\App\Http\Controllers\ExcelImportController::class, 'confirmImport']);
Route::get('/excel-import/agents', [\App\Http\Controllers\ExcelImportController::class, 'getAgents']);

// Inventory & Stores Routes
Route::prefix('inventory')->group(function () {
    Route::get('/items', [InventoryController::class, 'itemsIndex']);
    Route::post('/items', [InventoryController::class, 'storeItem']);
    Route::put('/items/{id}', [InventoryController::class, 'updateItem']);
    Route::delete('/items/{id}', [InventoryController::class, 'destroyItem']);
    Route::post('/update-stock', [InventoryController::class, 'updateStock']);
    Route::get('/settings', [InventoryController::class, 'getSettings']);
    Route::post('/settings', [InventoryController::class, 'saveSetting']);
    Route::put('/settings/{id}', [InventoryController::class, 'updateSetting']);
    Route::delete('/settings/{id}', [InventoryController::class, 'deleteSetting']);
    Route::get('/custody', [InventoryController::class, 'custodyIndex']);
    Route::get('/movements', [InventoryController::class, 'movementsIndex']);
    Route::post('/assign-custody', [InventoryController::class, 'assignCustody']);
    Route::post('/return-custody/{id}', [InventoryController::class, 'returnCustody']);
});

// ─── Treasury Routes (الخزنة) ─────────────────────────────────────────────────
Route::get('/treasury', [\App\Http\Controllers\TreasuryController::class, 'index']);
Route::post('/treasury', [\App\Http\Controllers\TreasuryController::class, 'store']);
Route::get('/treasury/balance', [\App\Http\Controllers\TreasuryController::class, 'balance']);
Route::get('/treasury/daily-report', [\App\Http\Controllers\TreasuryController::class, 'dailyReport']);
Route::get('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'show']);
Route::post('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'update']);
Route::delete('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'destroy']);

// ─── POS Machines Routes (ماكينات البطاقة) ───────────────────────────────────
Route::get('/pos-machines', [\App\Http\Controllers\PosMachineController::class, 'index']);
Route::post('/pos-machines', [\App\Http\Controllers\PosMachineController::class, 'store']);
Route::put('/pos-machines/{id}', [\App\Http\Controllers\PosMachineController::class, 'update']);
Route::delete('/pos-machines/{id}', [\App\Http\Controllers\PosMachineController::class, 'destroy']);
Route::post('/pos-machines/{id}/toggle-active', [\App\Http\Controllers\PosMachineController::class, 'toggleActive']);
Route::get('/pos-machines/dashboard', [\App\Http\Controllers\PosMachineController::class, 'dashboard']);

// ─── POS Transactions Routes ──────────────────────────────────────────────────
Route::get('/pos-transactions', [\App\Http\Controllers\PosMachineController::class, 'transactions']);
Route::post('/pos-transactions', [\App\Http\Controllers\PosMachineController::class, 'storeTransaction']);
Route::post('/pos-transactions/{id}', [\App\Http\Controllers\PosMachineController::class, 'updateTransaction']);
Route::delete('/pos-transactions/{id}', [\App\Http\Controllers\PosMachineController::class, 'destroyTransaction']);
Route::post('/pos-transactions/{id}/reconcile', [\App\Http\Controllers\PosMachineController::class, 'toggleReconcile']);

// ─── Agent Wallet & Loyalty Routes (المحفظة والتحفيز) ─────────────────────────
Route::prefix('agent-wallet')->group(function () {
    Route::get('/settings/loyalty', [\App\Http\Controllers\AgentWalletController::class, 'getLoyaltySettings']);
    Route::post('/settings/loyalty', [\App\Http\Controllers\AgentWalletController::class, 'saveLoyaltySettings']);
    Route::get('/{id}', [\App\Http\Controllers\AgentWalletController::class, 'getWalletDetails']);
    Route::get('/{id}/transactions', [\App\Http\Controllers\AgentWalletController::class, 'getTransactions']);
    Route::get('/{id}/withdrawals', [\App\Http\Controllers\AgentWalletController::class, 'getWithdrawals']);
    Route::post('/redeem', [\App\Http\Controllers\AgentWalletController::class, 'redeemPoints']);
    Route::post('/withdraw', [\App\Http\Controllers\AgentWalletController::class, 'requestWithdrawal']);
    Route::post('/withdrawals/{id}/status', [\App\Http\Controllers\AgentWalletController::class, 'updateWithdrawalStatus']);
    Route::post('/adjust', [\App\Http\Controllers\AgentWalletController::class, 'adjustWallet']);
    Route::get('/{id}/referrals', [\App\Http\Controllers\AgentWalletController::class, 'getReferrals']);
});

// ─── LIFO API Proxy Route ────────────────────────────────────────────────────
Route::any('/lifo-prod/{any}', function (Illuminate\Http\Request $request, $any) {
    $targetUrl = 'https://prodapi.lifo.ly/' . $any;

    $client = new \GuzzleHttp\Client([
        'verify'          => false,
        'timeout'         => 30.0,
        'connect_timeout' => 10.0,
        'proxy'           => '',
        'curl'            => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
    ]);

    // Headers to strip before forwarding to LIFO
    $stripHeaders = [
        'host', 'content-length', 'cookie', 'origin', 'referer',
        'accept-encoding', 'sec-fetch-dest', 'sec-fetch-mode', 'sec-fetch-site',
        'connection', 'authorization', 'content-type',
    ];

    // Flatten Laravel's array-of-arrays headers into plain strings for Guzzle
    $flatHeaders = [];
    foreach ($request->headers->all() as $key => $value) {
        if (!in_array(strtolower($key), $stripHeaders)) {
            $flatHeaders[$key] = is_array($value) ? implode(', ', $value) : $value;
        }
    }

    $options = [
        'headers'     => $flatHeaders,
        'query'       => $request->query(),
        'http_errors' => false,
    ];

    // For non-GET: forward body to LIFO
    // Supports both FormData (multipart) and JSON body
    if (!$request->isMethod('get')) {
        $contentType = strtolower($request->headers->get('Content-Type', ''));
        
        if (str_contains($contentType, 'application/json')) {
            // JSON body: decode and re-send via Guzzle 'json' option
            $rawBody = $request->getContent();
            if (!empty($rawBody)) {
                $decoded = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                    $options['json'] = $decoded;
                } else {
                    $options['body']                    = $rawBody;
                    $options['headers']['Content-Type'] = 'application/json';
                }
            }
        } else {
            // FormData or URL-encoded: Laravel already parsed it into $request->all()
            // Re-send as form_params (Content-Type: application/x-www-form-urlencoded)
            $allParams = $request->all();
            if (!empty($allParams)) {
                $options['form_params'] = $allParams;
            }
        }
    }

    try {
        $response = $client->request($request->method(), $targetUrl, $options);
        return response($response->getBody()->getContents(), $response->getStatusCode())
            ->header('Content-Type', $response->getHeaderLine('Content-Type') ?: 'application/json');
    } catch (\Exception $e) {
        return response()->json([
            'code'    => 0,
            'success' => false,
            'message' => 'تعذر الاتصال بخادم الاتحاد: ' . $e->getMessage(),
            'error'   => $e->getMessage(),
        ], 200);
    }
})->where('any', '.*');

// ─── LIFO Connection Diagnostic Endpoint ──────────────────────────────────────
Route::get('/test-lifo-connection', function () {
    $results = [];
    
    // Test 1: Resolve DNS
    $ip = gethostbyname('prodapi.lifo.ly');
    $results['dns_resolution'] = [
        'host' => 'prodapi.lifo.ly',
        'ip' => $ip,
        'success' => ($ip !== 'prodapi.lifo.ly')
    ];
    
    // Test 2: Curl test
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://prodapi.lifo.ly/api/countries/all");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4 in curl test
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $output = curl_exec($ch);
    $error = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    
    $results['curl_test'] = [
        'url' => 'https://prodapi.lifo.ly/api/countries/all',
        'http_code' => $info['http_code'],
        'error' => $error,
        'total_time' => $info['total_time'],
        'output_snippet' => substr($output, 0, 500)
    ];
    
    // Test 3: Laravel Http client test
    try {
        $res = \Illuminate\Support\Facades\Http::timeout(10)
            ->withoutVerifying()
            ->withOptions([
                'force_ip_resolve' => 'v4',
                'proxy' => ''
            ])
            ->get('https://prodapi.lifo.ly/api/countries/all');
        $results['laravel_http_test'] = [
            'status' => $res->status(),
            'body' => substr($res->body(), 0, 500)
        ];
    } catch (\Exception $e) {
        $results['laravel_http_test'] = [
            'error' => $e->getMessage()
        ];
    }
    
    return response()->json($results);
});


