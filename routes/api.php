<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

use App\Http\Controllers\AuthController;
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
use App\Http\Controllers\LifoReportController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\OldDocumentController;
use App\Http\Controllers\CanceledDocumentsController;
use App\Http\Controllers\SchoolStudentInsuranceDocumentController;
use App\Http\Controllers\CashInTransitInsuranceDocumentController;
use App\Http\Controllers\CargoInsuranceDocumentController;



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

Route::post('/login', [AuthController::class, 'login']);
Route::get('/user/{id}/refresh', [AuthController::class, 'refreshUser']);
Route::post('/unlock-session', [AuthController::class, 'unlockSession']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);

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
    Route::get('/general-manager', [UserController::class, 'getGeneralManager']);
    Route::get('/employees/all', [UserController::class, 'allEmployees']);
    Route::apiResource('departments', DepartmentController::class);
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

    // Office sub-users management endpoints
    Route::get('/office-users', [UserController::class, 'officeUsersIndex']);
    Route::post('/office-users', [UserController::class, 'storeOfficeUser']);
    Route::put('/office-users/{id}', [UserController::class, 'updateOfficeUser']);
    Route::post('/office-users/{id}/toggle-status', [UserController::class, 'toggleOfficeUserStatus']);
    Route::delete('/office-users/{id}', [UserController::class, 'destroyOfficeUser']);

});

// إدارة الوثائق القديمة
Route::get('/old-documents', [OldDocumentController::class, 'index']);
Route::post('/old-documents', [OldDocumentController::class, 'store']);

Route::post('/sync-user-permissions', [AuthController::class, 'syncUserPermissions']);
Route::get('/public/employees', [UserController::class, 'publicEmployees']);
Route::get('/public/departments', [DepartmentController::class, 'publicDepartments']);
Route::put('/users/{user}/email', [UserController::class, 'updateEmail']);
Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
Route::get('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'getMonthlyAccountClosure']);
Route::post('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'saveMonthlyAccountClosure']);
Route::delete('/branches-agents/monthly-account-closure/{id}', [BranchAgentController::class, 'deleteMonthlyAccountClosure']);
Route::get('/branches-agents/{id}/monthly-account-closure-print', [BranchAgentController::class, 'printMonthlyAccountClosure']);
Route::get('/branches-agents/monthly-account-closures-report', [BranchAgentController::class, 'getMonthlyAccountClosuresReport']);
Route::get('/branches-agents/pending-counts', [BranchAgentController::class, 'adminPendingCounts']);
Route::get('/financial-statistics/agent-monthly-ledger', [\App\Http\Controllers\FinancialStatisticsController::class, 'getAgentMonthlyLedger']);
Route::post('/financial-statistics/agent-monthly-ledger/payment', [\App\Http\Controllers\FinancialStatisticsController::class, 'updateMonthlyPayment']);
Route::post('/financial-statistics/agent-monthly-ledger/reset-payment', [\App\Http\Controllers\FinancialStatisticsController::class, 'resetMonthlyPayment']);
Route::post('/financial-statistics/agent-monthly-ledger/audit', [\App\Http\Controllers\FinancialStatisticsController::class, 'toggleMonthlyAudit']);
Route::get('/financial-statistics/agent-month-documents', [\App\Http\Controllers\FinancialStatisticsController::class, 'getAgentMonthDocuments']);
Route::put('/financial-statistics/agent-month-document', [\App\Http\Controllers\FinancialStatisticsController::class, 'updateAgentMonthDocument']);
Route::delete('/financial-statistics/agent-month-document', [\App\Http\Controllers\FinancialStatisticsController::class, 'deleteAgentMonthDocument']);
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
        'ظ‚ط±ط·ط§ط³ظٹط©',
        'طµظٹط§ظ†ط©',
        'ط®ط¯ظ…ط§طھ',
        'ط¥ظٹط¬ط§ط±',
        'ط¶ظٹط§ظپط©',
        'ط§ظ„طھط¹ظˆظٹط¶ط§طھ',
        'ظ‚ط±ط·ط§ط³ظٹظ‡ ظ…ظƒطھط¨ظٹظ‡ ظ…ط³طھظ‡ظ„ظƒظ‡',
        'ظ…طµط§ط±ظٹظپ (ط±طµظٹط¯ ظˆط§ط´طھط±ط§ظƒط§طھ ط­ظƒظˆظ…ظٹظ‡ (ظƒظ‡ط±ط¨ط§ط، -ط§ظ†طھط±ظ†طھ -ط±طµظٹط¯ ط§طھطµط§ظ„ط§طھ-ظ…ط§ط، -طµط±ظپ طµط­ظٹ ))',
        'ظ…طµط§ط±ظٹظپ ظ…ظˆط§ط¯ طھظ†ط¸ظٹظپ',
        'ط¹ظ‡ط¯ظ‡ ظ…ط§ظ„ظٹظ‡ ط®ط§طµظ‡ ط¨ط§ظ„ظ…ظˆط¸ظپظٹظ†',
        'طµظٹط§ظ†ط© (ط§ظ„ظƒطھط±ظˆظ†ظٹط§طھ - ط§ظ„ظ…ط¨ظ†ظ‰ - ط§ظ„ط§ط«ط§ط« -ط§ظ„ط® )',
        'طµظٹط§ظ†ظ‡ ط§ظ„ط³ظٹط§ط±ط§طھ ط§ظ„ط®ط§طµظ‡ ط¨ط§ظ„ظ…ظˆط¸ظپظٹظ† ظˆط§ظ„ط®ط¯ظ…ط§طھ',
        'ط§ظ„ظƒطھط±ظˆظ†ظٹط§طھ ط«ط§ط¨طھظ‡',
        'ط¯ط¹ط§ظٹظ‡ ظˆط§ط¹ظ„ط§ظ† ظˆظ‡ط¯ط§ظٹط§ ظ…ط³طھظ‡ظ„ظƒظ‡ (( ط®ط§طµ ط¨ط§ظ„ظˆظƒظ„ط§ط، ))',
        'ط±ط³ظˆظ… ظˆظ…طµط§ط±ظٹظپ ط§ط´طھط±ط§ظƒط§طھ ط§ظ„ظ…ط¹ط§ط±ط¶ ظˆط§ظ„ط§ط¬طھظ…ط§ط¹ط§طھ ط§ظ„ط®ط§طµظ‡ ط¨ط§ظ„ط´ط±ظƒظ‡',
        'ط±ط³ظˆظ… ط§طµط¯ط§ط± ظˆطھط¬ط¯ظٹط¯ ط؛ط±ظپظ‡ ط§ظ„طھط¬ط§ط±ظ‡ ظˆط§ظ„طµظ†ط§ط¹ظ‡ ظˆط§ظ„ط²ط±ط§ط¹ظ‡',
        'ظ‚ط±ط·ط§ط³ظٹظ‡ ظ…ظƒطھط¨ظٹظ‡ ط«ط§ط¨طھظ‡',
        'ط±ط³ظˆظ… ط§ط´طھط±ط§ظƒط§طھ ط§ط¹ط§ط¯ظ‡ ط§ظ„طھط§ظ…ظٹظ†',
        'ظ…طµظ„ط­ط© ط§ظ„ط¶ط±ط§ط¦ط¨ ظˆط§ظ„ظ…ظٹط²ط§ظ†ظٹط§طھ'
    ];
    $insertData = [];
    foreach ($cats as $cat) {
        $insertData[] = ['name' => $cat, 'created_at' => now(), 'updated_at' => now()];
    }
    \Illuminate\Support\Facades\DB::table('expense_categories')->insert($insertData);
    return 'طھظ… طھظ†ط¸ظٹظپ ط§ظ„ظپط¦ط§طھ ط¨ظ†ط¬ط§ط­! ط¬ظ…ظٹط¹ ط§ظ„ظپط¦ط§طھ ط§ظ„ظˆظ‡ظ…ظٹط© طھظ… ظ…ط³ط­ظ‡ط§طŒ ظˆطھظ…طھ ط¥ط¶ط§ظپط© ط§ظ„ظپط¦ط§طھ ط§ظ„ط±ط³ظ…ظٹط© ظپظ‚ط·. ظٹظ…ظƒظ†ظƒ ط§ظ„ط¹ظˆط¯ط© ظ„ظ„ظ†ط¸ط§ظ… ط§ظ„ط¢ظ† ظˆطھط­ط¯ظٹط« ط§ظ„طµظپط­ط©.';
});

Route::apiResource('school-student-insurance', 'App\Http\Controllers\SchoolStudentInsuranceDocumentController');
Route::get('/school-student-insurance/{id}/print', ['App\Http\Controllers\SchoolStudentInsuranceDocumentController', 'print']);
Route::apiResource('cash-in-transit-insurance', 'App\Http\Controllers\CashInTransitInsuranceDocumentController');
Route::get('/cash-in-transit-insurance/{id}/print', ['App\Http\Controllers\CashInTransitInsuranceDocumentController', 'print']);
Route::apiResource('cargo-insurance', 'App\Http\Controllers\CargoInsuranceDocumentController');
Route::get('/cargo-insurance/{id}/print', ['App\Http\Controllers\CargoInsuranceDocumentController', 'print']);
Route::get('/branches-agents/{id}/financial-stats', [BranchAgentController::class, 'getAgentFinancialStats']);
Route::get('/global-financial-stats', [BranchAgentController::class, 'getGlobalFinancialStats']);
Route::get('/branches-agents/{id}/print', [BranchAgentController::class, 'print']);
Route::get('/branches-agents/{id}/print-permit', [BranchAgentController::class, 'printPermit']);
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
Route::get('/financial-statistics/live-agents-production', [FinancialStatisticsController::class, 'getLiveAgentsProduction']);
Route::apiResource('cities', CityController::class);
Route::apiResource('plates', PlateController::class);
Route::apiResource('vehicle-types', VehicleTypeController::class);
Route::apiResource('colors', ColorController::class)->only(['index', 'store', 'update', 'destroy']);
Route::apiResource('insurance-documents', InsuranceDocumentController::class);
Route::get('/insurance-documents/{id}/print', [InsuranceDocumentController::class, 'print']);
Route::post('/insurance-documents/{id}/transfer-ownership', [InsuranceDocumentController::class, 'transferOwnership']);
Route::get('/insurance-documents/{id}/ownership-transfer-history', [InsuranceDocumentController::class, 'getOwnershipTransferHistory']);

// â”€â”€â”€ EIDC Authority Integration Routes (طھط£ظ…ظٹظ† ط¥ط¬ط¨ط§ط±ظٹ ط³ظٹط§ط±ط§طھ) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/insurance-documents/eidc/vehicle-types', [InsuranceDocumentController::class, 'eidcVehicleTypes']);
Route::get('/insurance-documents/eidc/vehicle-specs', [InsuranceDocumentController::class, 'eidcVehicleSpecs']);
Route::get('/insurance-documents/eidc/vehicle-details', [InsuranceDocumentController::class, 'eidcVehicleDetails']);
Route::post('/insurance-documents/eidc/inquiry', [InsuranceDocumentController::class, 'eidcInquiry']);
Route::get('/insurance-documents/eidc/serial-stats', [InsuranceDocumentController::class, 'eidcSerialStats']);
Route::post('/insurance-documents/{id}/eidc-cancel', [InsuranceDocumentController::class, 'eidcCancel']);
Route::post('/insurance-documents/{id}/eidc-retry', [InsuranceDocumentController::class, 'eidcRetrySync']);
Route::get('/insurance-documents/{id}/eidc-print', [InsuranceDocumentController::class, 'eidcPrintProxy']);
Route::post('/insurance-documents/eidc-sync-all', [InsuranceDocumentController::class, 'eidcSyncFromAuthority']);
Route::post('/insurance-documents/{id}/cancel', [InsuranceDocumentController::class, 'cancel']);



Route::post('/international-insurance-documents/sync-union', [InternationalInsuranceDocumentController::class, 'syncFromUnion']);
Route::get('/international-insurance-documents/sync-union-status', [InternationalInsuranceDocumentController::class, 'syncStatus']);
Route::apiResource('international-insurance-documents', InternationalInsuranceDocumentController::class);
Route::get('/international-insurance-documents/{id}/print', [InternationalInsuranceDocumentController::class, 'print']);
Route::post('/international-insurance-documents/{id}/cancel', [InternationalInsuranceDocumentController::class, 'cancel']);

Route::apiResource('travel-insurance-documents', TravelInsuranceDocumentController::class);
Route::get('/travel-insurance-documents/{id}/print', [TravelInsuranceDocumentController::class, 'print']);
Route::post('/travel-insurance-documents/{id}/cancel', [TravelInsuranceDocumentController::class, 'cancel']);

Route::apiResource('resident-insurance-documents', ResidentInsuranceDocumentController::class);
Route::get('/resident-insurance-documents/{id}/print', [ResidentInsuranceDocumentController::class, 'print']);
Route::post('/resident-insurance-documents/{id}/cancel', [ResidentInsuranceDocumentController::class, 'cancel']);

Route::get('/marine-engine-models', [MarineEngineModelController::class, 'index']);
Route::post('/marine-engine-models', [MarineEngineModelController::class, 'store']);

Route::apiResource('marine-structure-insurance-documents', MarineStructureInsuranceDocumentController::class)->parameters([
    'marine-structure-insurance-documents' => 'document'
]);
Route::get('/marine-structure-insurance-documents/{document}/print', [MarineStructureInsuranceDocumentController::class, 'print']);
Route::post('/marine-structure-insurance-documents/{document}/cancel', [MarineStructureInsuranceDocumentController::class, 'cancel']);

Route::apiResource('professional-liability-insurance-documents', ProfessionalLiabilityInsuranceDocumentController::class)->parameters([
    'professional-liability-insurance-documents' => 'document'
]);
Route::get('/professional-liability-insurance-documents/{document}/print', [ProfessionalLiabilityInsuranceDocumentController::class, 'print']);
Route::post('/professional-liability-insurance-documents/{document}/cancel', [ProfessionalLiabilityInsuranceDocumentController::class, 'cancel']);

// Routes for professions management
Route::get('/professions', [ProfessionController::class, 'index']);
Route::post('/professions', [ProfessionController::class, 'store']);
Route::delete('/professions/{id}', [ProfessionController::class, 'destroy']);

use App\Http\Controllers\DocumentRequestController;
Route::get('/document-requests/pending-count', [DocumentRequestController::class, 'pendingCount']);
Route::apiResource('document-requests', DocumentRequestController::class);

Route::apiResource('personal-accident-insurance-documents', PersonalAccidentInsuranceDocumentController::class)->parameters([
    'personal-accident-insurance-documents' => 'document'
]);
Route::get('/personal-accident-insurance-documents/{document}/print', [PersonalAccidentInsuranceDocumentController::class, 'print']);
Route::post('/personal-accident-insurance-documents/{document}/cancel', [PersonalAccidentInsuranceDocumentController::class, 'cancel']);

// School Student, Cash in Transit, Cargo
Route::apiResource('school-student-insurance-documents', SchoolStudentInsuranceDocumentController::class)->parameters([
    'school-student-insurance-documents' => 'id'
]);
Route::post('/school-student-insurance-documents/{id}/cancel', [SchoolStudentInsuranceDocumentController::class, 'cancel']);

Route::apiResource('cash-in-transit-insurance-documents', CashInTransitInsuranceDocumentController::class)->parameters([
    'cash-in-transit-insurance-documents' => 'id'
]);
Route::post('/cash-in-transit-insurance-documents/{id}/cancel', [CashInTransitInsuranceDocumentController::class, 'cancel']);

Route::apiResource('cargo-insurance-documents', CargoInsuranceDocumentController::class)->parameters([
    'cargo-insurance-documents' => 'id'
]);
Route::post('/cargo-insurance-documents/{id}/cancel', [CargoInsuranceDocumentController::class, 'cancel']);


// â”€â”€â”€ Canceled Documents Management (ط¥ط¯ط§ط±ط© ط§ظ„ظˆط«ط§ط¦ظ‚ ط§ظ„ظ…ظ„ط؛ظٹط©) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/canceled-documents', [CanceledDocumentsController::class, 'index']);
Route::get('/canceled-documents/stats', [CanceledDocumentsController::class, 'stats']);

Route::apiResource('external-entities', \App\Http\Controllers\ExternalEntityController::class);
Route::apiResource('mail-documents', \App\Http\Controllers\MailDocumentController::class);
Route::apiResource('company-documents', CompanyDocumentController::class);
Route::apiResource('rental-vouchers', RentalVoucherController::class);

Route::get('/git-pull', function () {
    try {
        $output = shell_exec('git pull 2>&1');
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        return response()->json([
            'status' => 'success',
            'git_output' => $output,
            'artisan_output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/clear-cache', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        \Illuminate\Support\Facades\Artisan::call('config:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        return "Cache cleared successfully!";
    } catch (\Exception $e) {
        return "Error: " . $e->getMessage();
    }
});

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
Route::post('/claims/{id}/submit-compensation', [ClaimController::class, 'submitCompensation']);
Route::post('/claims/{id}/approve-payment', [ClaimController::class, 'approvePayment']);
Route::post('/claims/{id}/reject-payment', [ClaimController::class, 'rejectPayment']);

// â”€â”€â”€ Excel Import Routes (ط§ط³طھظٹط±ط§ط¯ ظ…ظ„ظپط§طھ Excel) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€â”€ Treasury Routes (ط§ظ„ط®ط²ظ†ط©) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/treasury', [\App\Http\Controllers\TreasuryController::class, 'index']);
Route::post('/treasury', [\App\Http\Controllers\TreasuryController::class, 'store']);
Route::get('/treasury/balance', [\App\Http\Controllers\TreasuryController::class, 'balance']);
Route::get('/treasury/daily-report', [\App\Http\Controllers\TreasuryController::class, 'dailyReport']);
Route::get('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'show']);
Route::post('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'update']);
Route::delete('/treasury/{id}', [\App\Http\Controllers\TreasuryController::class, 'destroy']);

// â”€â”€â”€ POS Machines Routes (ظ…ط§ظƒظٹظ†ط§طھ ط§ظ„ط¨ط·ط§ظ‚ط©) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/pos-machines', [\App\Http\Controllers\PosMachineController::class, 'index']);
Route::post('/pos-machines', [\App\Http\Controllers\PosMachineController::class, 'store']);
Route::put('/pos-machines/{id}', [\App\Http\Controllers\PosMachineController::class, 'update']);
Route::delete('/pos-machines/{id}', [\App\Http\Controllers\PosMachineController::class, 'destroy']);
Route::post('/pos-machines/{id}/toggle-active', [\App\Http\Controllers\PosMachineController::class, 'toggleActive']);
Route::get('/pos-machines/dashboard', [\App\Http\Controllers\PosMachineController::class, 'dashboard']);

// â”€â”€â”€ POS Transactions Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/pos-transactions', [\App\Http\Controllers\PosMachineController::class, 'transactions']);
Route::post('/pos-transactions', [\App\Http\Controllers\PosMachineController::class, 'storeTransaction']);
Route::post('/pos-transactions/{id}', [\App\Http\Controllers\PosMachineController::class, 'updateTransaction']);
Route::delete('/pos-transactions/{id}', [\App\Http\Controllers\PosMachineController::class, 'destroyTransaction']);
Route::post('/pos-transactions/{id}/reconcile', [\App\Http\Controllers\PosMachineController::class, 'toggleReconcile']);

// â”€â”€â”€ Agent Wallet & Loyalty Routes (ط§ظ„ظ…ط­ظپط¸ط© ظˆط§ظ„طھط­ظپظٹط²) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

// â”€â”€â”€ LIFO API Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::any('/lifo-prod/{any}', [LifoReportController::class, 'lifoProxy'])->where('any', '.*');
Route::post('/lifo-reports/cards-paginated', [LifoReportController::class, 'cardsPaginated']);
Route::post('/lifo-reports/reports-paginated', [LifoReportController::class, 'reportsPaginated']);
Route::post('/lifo-reports/inventory-summary', [LifoReportController::class, 'inventorySummary']);
Route::post('/lifo-reports/offices-aggregated', [LifoReportController::class, 'officesAggregated']);
Route::post('/lifo-reports/requests-list', [LifoReportController::class, 'requestsList']);
Route::post('/lifo-reports/dashboard-summary', [LifoReportController::class, 'dashboardSummary']);
Route::get('/test-lifo-connection', [LifoReportController::class, 'testLifoConnection']);

// â”€â”€â”€ Website Settings & Management Routes â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
Route::get('/public/website-settings', [\App\Http\Controllers\WebsiteSettingsController::class, 'getPublicSettings']);
Route::get('/public/media-posts', [\App\Http\Controllers\WebsiteSettingsController::class, 'getPublicMediaPosts']);
Route::post('/public/insurance-requests', [\App\Http\Controllers\PublicInsuranceRequestController::class, 'store']);

Route::middleware('auth:sanctum')->group(function () {
    // ط¥ط¹ط¯ط§ط¯ط§طھ ط§ظ„ظ…ظˆظ‚ط¹
    Route::get('/website-settings', [\App\Http\Controllers\WebsiteSettingsController::class, 'getSettings']);
    Route::post('/website-settings', [\App\Http\Controllers\WebsiteSettingsController::class, 'saveSettings']);

    // ط§ظ„ظ…ط±ظƒط² ط§ظ„ط¥ط¹ظ„ط§ظ…ظٹ
    Route::get('/website-settings/media-posts', [\App\Http\Controllers\WebsiteSettingsController::class, 'mediaPostsIndex']);
    Route::post('/website-settings/media-posts', [\App\Http\Controllers\WebsiteSettingsController::class, 'mediaPostsStore']);
    Route::post('/website-settings/media-posts/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'mediaPostsUpdate']);
    Route::delete('/website-settings/media-posts/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'mediaPostsDestroy']);

    // ط¨ظ†ط±ط§طھ ط§ظ„طµظپط­ط© ط§ظ„ط±ط¦ظٹط³ظٹط©
    Route::get('/website-settings/sliders', [\App\Http\Controllers\WebsiteSettingsController::class, 'slidersIndex']);
    Route::post('/website-settings/sliders', [\App\Http\Controllers\WebsiteSettingsController::class, 'slidersStore']);
    Route::post('/website-settings/sliders/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'slidersUpdate']);
    Route::delete('/website-settings/sliders/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'slidersDestroy']);

    // ط®ط¯ظ…ط§طھ ط§ظ„طµظپط­ط© ط§ظ„ط±ط¦ظٹط³ظٹط©
    Route::get('/website-settings/services', [\App\Http\Controllers\WebsiteSettingsController::class, 'servicesIndex']);
    Route::post('/website-settings/services', [\App\Http\Controllers\WebsiteSettingsController::class, 'servicesStore']);
    Route::post('/website-settings/services/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'servicesUpdate']);
    Route::delete('/website-settings/services/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'servicesDestroy']);

    // ط£ظ†ظˆط§ط¹ ط§ظ„طھط£ظ…ظٹظ† (طµظپط­ط© ط§ظ„طھط£ظ…ظٹظ†ط§طھ)
    Route::get('/website-settings/insurance-types', [\App\Http\Controllers\WebsiteSettingsController::class, 'insuranceTypesIndex']);
    Route::post('/website-settings/insurance-types', [\App\Http\Controllers\WebsiteSettingsController::class, 'insuranceTypesStore']);
    Route::post('/website-settings/insurance-types/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'insuranceTypesUpdate']);
    Route::delete('/website-settings/insurance-types/{id}', [\App\Http\Controllers\WebsiteSettingsController::class, 'insuranceTypesDestroy']);

    // ط·ظ„ط¨ط§طھ ط§ظ„طھط£ظ…ظٹظ† ط§ظ„ط¹ط§ظ…ط©
    Route::get('/public-insurance-requests', [\App\Http\Controllers\PublicInsuranceRequestController::class, 'index']);
    Route::get('/public-insurance-requests/{id}', [\App\Http\Controllers\PublicInsuranceRequestController::class, 'show']);
    Route::put('/public-insurance-requests/{id}', [\App\Http\Controllers\PublicInsuranceRequestController::class, 'update']);
    Route::delete('/public-insurance-requests/{id}', [\App\Http\Controllers\PublicInsuranceRequestController::class, 'destroy']);
});


// Insurance Conditions API (شروط الوثيقة لكل التأمينات)
Route::get('/insurance-conditions', [\App\Http\Controllers\InsuranceConditionController::class, 'index']);
Route::get('/insurance-conditions/{type}', [\App\Http\Controllers\InsuranceConditionController::class, 'show']);
Route::post('/insurance-conditions', [\App\Http\Controllers\InsuranceConditionController::class, 'store']);