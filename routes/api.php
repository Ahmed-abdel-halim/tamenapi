<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BranchAgentController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\PlateController;
use App\Http\Controllers\VehicleTypeController;
use App\Http\Controllers\InsuranceDocumentController;
use App\Http\Controllers\InternationalInsuranceDocumentController;
use App\Http\Controllers\TravelInsuranceDocumentController;
use App\Http\Controllers\ResidentInsuranceDocumentController;
use App\Http\Controllers\MarineStructureInsuranceDocumentController;
use App\Http\Controllers\MarineEngineModelController;
use App\Http\Controllers\ProfessionalLiabilityInsuranceDocumentController;
use App\Http\Controllers\PersonalAccidentInsuranceDocumentController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ColorController;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

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
    
    // جلب معلومات الوكيل/الفرع المرتبط بالمستخدم (إذا كان موجوداً)
    $branchAgent = $user->branchAgent;
    $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);
    
    // إذا أردت العودة بتوكن Sanctum/Api بسهولة يمكن ذلك لاحقاً
    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'is_admin' => $user->is_admin ?? false,
            'authorized_documents' => $authorizedDocuments,
            'branch_agent_id' => $branchAgent ? $branchAgent->id : null,
        ],
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
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'المستخدم غير موجود',
            'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
        ], 404);
    }
});

Route::apiResource('users', UserController::class);

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
Route::put('/users/{user}/email', [UserController::class, 'updateEmail']);
Route::put('/users/{user}/password', [UserController::class, 'updatePassword']);
Route::get('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'getMonthlyAccountClosure']);
Route::post('/branches-agents/monthly-account-closure', [BranchAgentController::class, 'saveMonthlyAccountClosure']);
Route::get('/branches-agents/{id}/monthly-account-closure-print', [BranchAgentController::class, 'printMonthlyAccountClosure']);
Route::get('/branches-agents/monthly-account-closures-report', [BranchAgentController::class, 'getMonthlyAccountClosuresReport']);
Route::apiResource('branches-agents', BranchAgentController::class);
Route::get('/branches-agents/{id}/print', [BranchAgentController::class, 'print']);
Route::get('/branches-agents/{id}/account-report', [BranchAgentController::class, 'accountReport']);
Route::get('/dashboard/statistics', [BranchAgentController::class, 'getStatistics']);
Route::get('/dashboard/latest-documents', [BranchAgentController::class, 'getLatestDocuments']);
Route::apiResource('cities', CityController::class);
Route::apiResource('plates', PlateController::class);
Route::apiResource('vehicle-types', VehicleTypeController::class);
Route::apiResource('colors', ColorController::class)->only(['index', 'store', 'destroy']);
Route::apiResource('insurance-documents', InsuranceDocumentController::class);
Route::get('/insurance-documents/{id}/print', [InsuranceDocumentController::class, 'print']);
Route::post('/insurance-documents/{id}/transfer-ownership', [InsuranceDocumentController::class, 'transferOwnership']);
Route::get('/insurance-documents/{id}/ownership-transfer-history', [InsuranceDocumentController::class, 'getOwnershipTransferHistory']);

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

Route::apiResource('personal-accident-insurance-documents', PersonalAccidentInsuranceDocumentController::class)->parameters([
    'personal-accident-insurance-documents' => 'document'
]);
Route::get('/personal-accident-insurance-documents/{document}/print', [PersonalAccidentInsuranceDocumentController::class, 'print']);
