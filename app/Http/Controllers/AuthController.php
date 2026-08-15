<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\BranchAgent;

class AuthController extends Controller
{
    /**
     * Handle user login (Local + LIFO fallback)
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();
        $localAuthSuccess = false;

        if ($user && Hash::check($request->password, $user->password)) {
            $localAuthSuccess = true;
        }

        // Fallback to LIFO API if local auth fails
        if (!$localAuthSuccess) {
            try {
                $response = Http::timeout(15)
                    ->withoutVerifying()
                    ->asForm()
                    ->post('https://prodapi.lifo.ly/api/auth/offices', [
                        'user_name' => $request->username,
                        'pass_word' => $request->password,
                    ]);

                if ($response->successful() && $response->json('code') === 1) {
                    $lifoData = $response->json('data') ?? [];
                    Log::info('LIFO login fallback success for user: ' . $request->username, ['response' => $lifoData]);

                    // Extract office details
                    $officeId = $lifoData['offices_id'] ?? $lifoData['office_id'] ?? null;
                    if (!$officeId && isset($lifoData['id'])) {
                        $officeId = $lifoData['id'];
                    }

                    if (!$officeId) {
                        $fallbacks = [
                            'ahmed2' => '2403',
                        ];
                        $officeId = $fallbacks[$request->username] ?? null;
                    }

                    // If we have an office ID, we check/create the BranchAgent
                    $branchAgent = null;
                    if ($officeId) {
                        $branchAgent = BranchAgent::where('user_id', $user?->id)
                            ->orWhereHas('users', function ($q) use ($officeId) {
                                $q->where('lifo_office_id', $officeId);
                            })
                            ->first();

                        if (!$branchAgent) {
                            $officeName = $lifoData['name'] ?? $lifoData['agency_name'] ?? "مكتب اتحاد " . $officeId;
                            $managerName = $lifoData['fullname_manger'] ?? $officeName;

                            // Generate code BKxxxx
                            $lastAgent = BranchAgent::where('code', 'like', 'BK%')->orderBy('id', 'desc')->first();
                            $nextNumber = $lastAgent ? ((int)substr($lastAgent->code, 2) + 1) : 1;
                            do {
                                $code = 'BK' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
                                $nextNumber++;
                            } while (BranchAgent::where('code', $code)->exists());

                            $branchAgent = BranchAgent::create([
                                'type' => 'وكيل',
                                'code' => $code,
                                'agency_name' => $officeName,
                                'agent_name' => $managerName,
                                'status' => 'نشط',
                                'authorized_documents' => ['تأمين سيارات دولي'],
                                'document_percentages' => [],
                            ]);
                        }
                    }

                    // Create or update the local user
                    $user = User::updateOrCreate(
                        ['username' => $request->username],
                        [
                            'name' => $lifoData['fullname_manger'] ?? $lifoData['name'] ?? $request->username,
                            'password' => Hash::make($request->password),
                            'lifo_username' => $request->username,
                            'lifo_password' => $request->password,
                            'lifo_office_id' => $officeId,
                            'branch_agent_id' => $branchAgent?->id,
                            'authorized_documents' => ['تأمين سيارات دولي'],
                            'is_active' => true,
                        ]
                    );

                    // Link user to agent if needed
                    if ($branchAgent && !$branchAgent->user_id && $request->username === ($lifoData['username'] ?? $request->username)) {
                        $branchAgent->user_id = $user->id;
                        $branchAgent->save();
                    }

                    $localAuthSuccess = true;
                }
            } catch (\Exception $e) {
                Log::error('LIFO login fallback failed: ' . $e->getMessage());
            }
        }

        if (!$localAuthSuccess || !$user) {
            return response()->json(['message' => 'بيانات الدخول غير صحيحة'], 401);
        }

        if (isset($user->is_active) && $user->is_active === false) {
            return response()->json(['message' => 'هذا الحساب غير نشط حالياً، يرجى مراجعة الإدارة'], 403);
        }

        if (isset($user->is_blocked) && $user->is_blocked === true) {
            return response()->json(['message' => 'هذا الحساب محظور حالياً'], 403);
        }

        // جلب معلومات الوكيل/الفرع المرتبط بالمستخدم
        $branchAgent = $user->branchAgent ?? BranchAgent::where('user_id', $user->id)->first();
        $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);

        // إنشاء توكن Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => [
                'id'                   => $user->id,
                'username'             => $user->username,
                'name'                 => $user->name,
                'is_admin'             => $user->is_admin ?? false,
                'authorized_documents' => $authorizedDocuments,
                'branch_agent_id'      => $branchAgent ? $branchAgent->id : null,
                'is_blocked'           => $user->is_blocked ?? false,
                'lifo_username'        => $user->lifo_username ?? null,
                'lifo_password'        => $user->lifo_password ?? null,
                'lifo_office_id'       => $user->lifo_office_id ?? null,
                'lifo_permissions'     => $user->lifo_permissions ?? [],
                'lifo_user_id'         => $user->lifo_user_id ?? null,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Unlock session after inactivity timeout
     */
    public function unlockSession(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();
        $authSuccess = false;

        if ($user && Hash::check($request->password, $user->password)) {
            $authSuccess = true;
        }

        // LIFO fallback إذا لزم الأمر
        if (!$authSuccess) {
            try {
                $response = Http::timeout(15)
                    ->withoutVerifying()
                    ->asForm()
                    ->post('https://prodapi.lifo.ly/api/auth/offices', [
                        'user_name' => $request->username,
                        'pass_word' => $request->password,
                    ]);

                if ($response->successful() && $response->json('code') === 1) {
                    $authSuccess = true;
                    if ($user) {
                        $user->password = Hash::make($request->password);
                        $user->save();
                    }
                }
            } catch (\Exception $e) {
                Log::error('LIFO unlock fallback failed: ' . $e->getMessage());
            }
        }

        if (!$authSuccess || !$user) {
            return response()->json(['message' => 'كلمة المرور غير صحيحة، يرجى المحاولة مجدداً'], 401);
        }

        if (isset($user->is_active) && $user->is_active === false) {
            return response()->json(['message' => 'هذا الحساب غير نشط حالياً، يرجى مراجعة الإدارة'], 403);
        }

        if (isset($user->is_blocked) && $user->is_blocked === true) {
            return response()->json(['message' => 'هذا الحساب محظور حالياً'], 403);
        }

        $branchAgent = $user->branchAgent ?? BranchAgent::where('user_id', $user->id)->first();
        $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء قفل الجلسة بنجاح',
            'user' => [
                'id'                   => $user->id,
                'username'             => $user->username,
                'name'                 => $user->name,
                'is_admin'             => $user->is_admin ?? false,
                'authorized_documents' => $authorizedDocuments,
                'branch_agent_id'      => $branchAgent ? $branchAgent->id : null,
                'is_blocked'           => $user->is_blocked ?? false,
                'lifo_username'        => $user->lifo_username ?? null,
                'lifo_password'        => $user->lifo_password ?? null,
                'lifo_office_id'       => $user->lifo_office_id ?? null,
                'lifo_permissions'     => $user->lifo_permissions ?? [],
                'lifo_user_id'         => $user->lifo_user_id ?? null,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Refresh user profile and permissions
     */
    public function refreshUser(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $branchAgent = $user->branchAgent ?? BranchAgent::where('user_id', $user->id)->first();
            $authorizedDocuments = $user->authorized_documents ?? ($branchAgent ? ($branchAgent->authorized_documents ?? []) : []);

            return response()->json([
                'success' => true,
                'user' => [
                    'id'                   => $user->id,
                    'username'             => $user->username,
                    'name'                 => $user->name,
                    'is_admin'             => $user->is_admin ?? false,
                    'authorized_documents' => $authorizedDocuments,
                    'branch_agent_id'      => $branchAgent ? $branchAgent->id : null,
                    'is_blocked'           => $user->is_blocked ?? false,
                    'lifo_username'        => $user->lifo_username ?? null,
                    'lifo_password'        => $user->lifo_password ?? null,
                    'lifo_office_id'       => $user->lifo_office_id ?? null,
                    'lifo_permissions'     => $user->lifo_permissions ?? [],
                    'lifo_user_id'         => $user->lifo_user_id ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'المستخدم غير موجود',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 404);
        }
    }

    /**
     * Logout and revoke current token
     */
    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()?->delete();
        }
        return response()->json(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح']);
    }

    /**
     * Sync user permissions with branch agents
     */
    public function syncUserPermissions(Request $request)
    {
        try {
            $branchAgents = BranchAgent::whereNotNull('user_id')->get();
            $updated = 0;

            foreach ($branchAgents as $agent) {
                if ($agent->user_id && $agent->authorized_documents) {
                    $user = User::find($agent->user_id);
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
    }
}
