<?php

namespace App\Http\Controllers;

use App\Models\EmployeeSalaryHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);

            $query = User::with('branchAgent:id,user_id,type,agency_name,agent_name');

            // استبعاد أي مستخدم مرتبط ببيانات وكيل أو فرع من قائمة الموظفين
            $query->whereNull('branch_agent_id')
                  ->whereNotIn('id', function ($sub) {
                      $sub->select('user_id')->from('branches_agents')->whereNotNull('user_id');
                  });

            // الفرز حسب الأقدمية (تاريخ مباشرة العمل)
            $query->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
                  ->orderBy('start_date', 'asc')
                  ->orderBy('id', 'asc');

            // الفلترة حسب درجة الوصول (الكل، مدير، موظف عادي)
            if ($request->filled('role') && $request->role !== 'all') {
                if ($request->role === 'admin') {
                    $query->where('is_admin', true);
                } else {
                    $query->where('is_admin', false);
                }
            }

            // الفلترة حسب المسمى الوظيفي
            if ($request->filled('job_title') && $request->job_title !== 'all') {
                $query->where('job_title', 'like', "%{$request->job_title}%");
            }

            // الفلترة حسب الصلاحية (Authorized Documents)
            if ($request->filled('permission') && $request->permission !== 'all') {
                $query->whereJsonContains('authorized_documents', $request->permission);
            }

            // الفلترة حسب الحالة (نشط / غير نشط)
            if ($request->filled('active') && $request->active !== 'all') {
                $query->where('is_active', $request->active == '1');
            }

            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage);

            $users->getCollection()->transform(function ($user) {
                $userData = $user->toArray();
                $userData['is_admin'] = $user->is_admin ?? false;
                $userData['authorized_documents'] = $user->authorized_documents ?? [];
                $userData['user_type'] = 'مستخدم عادي';
                $userData['branch_agent_info'] = null;

                if ($user->is_admin) {
                    $userData['user_type'] = 'مدير';
                } elseif ($user->branchAgent) {
                    $userData['user_type'] = $user->branchAgent->type;
                    $userData['branch_agent_info'] = [
                        'id' => $user->branchAgent->id,
                        'type' => $user->branchAgent->type,
                        'agency_name' => $user->branchAgent->agency_name,
                        'agent_name' => $user->branchAgent->agent_name,
                    ];
                }

                return $userData;
            });

            return response()->json([
                'data' => $users->items(),
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error in UserController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب قائمة الموظفين',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string',
            'email' => 'nullable|email',
            'password' => 'required|string|min:6',
            'is_admin' => 'nullable|boolean',
            'authorized_documents' => 'nullable|array',
            'salary' => 'nullable|numeric',
            'national_id_number' => 'nullable|string|max:64',
            'job_title' => 'nullable|string|max:191',
            // الموظفين
            'full_name_quad' => 'nullable|string',
            'mother_name' => 'nullable|string',
            'gender' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'nationality' => 'nullable|string',
            'social_status' => 'nullable|string',
            'qualification' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'personal_phone' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'financial_number' => 'nullable|string',
            'job_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'account_number' => 'nullable|string',
            'start_date' => 'nullable|date',
            'working_hours_from' => 'nullable|string',
            'working_hours_to' => 'nullable|string',
            'working_days_from' => 'nullable|string',
            'working_days_to' => 'nullable|string',
            'contract_type' => 'nullable|string',
            'contract_duration' => 'nullable|string',
            'contract_conditions' => 'nullable|string',
            'housing_allowance' => 'nullable|numeric',
            'transportation_allowance' => 'nullable|numeric',
            'communication_allowance' => 'nullable|numeric',
            'fixed_bonuses' => 'nullable|numeric',
            'fixed_fines' => 'nullable|numeric',
            'hourly_leave_deduction' => 'nullable|numeric',
            'daily_leave_deduction' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'show_on_landing' => 'nullable|boolean',
            'tax_percentage' => 'nullable|numeric',
            'social_security_percentage' => 'nullable|numeric',
            'salary_type' => 'nullable|string|in:monthly,hourly',
            'hourly_rate' => 'nullable|numeric',
            'eidc_username' => 'nullable|string|max:191',
            'eidc_password' => 'nullable|string|max:191',
            'lifo_username' => 'nullable|string|max:191',
            'lifo_password' => 'nullable|string|max:191',
            'lifo_office_id' => 'nullable|string|max:191',
            'apply_tax' => 'nullable|boolean',
            'apply_social_security' => 'nullable|boolean',
            'tax_file_number' => 'nullable|string|max:191',
            'social_security_file_number' => 'nullable|string|max:191',
            'end_date' => 'nullable|date',
        ]);

        $data = $validated;

        // توليد الرقم المالي والوظيفي تلقائياً إذا تم تركه فارغاً
        $nextId = (User::max('id') ?? 0) + 1;
        $currentYear = date('Y');

        if (empty($data['financial_number'])) {
            $data['financial_number'] = "MLI" . $nextId;
        }
        if (empty($data['job_number'])) {
            $data['job_number'] = $nextId . "-" . $currentYear;
        }

        $data['password'] = Hash::make($request->password);
        $data['is_admin'] = $request->is_admin ?? false;

        if ($data['is_admin']) {
            $data['authorized_documents'] = null;
        }

        $user = User::create($data);

        if ($request->filled('salary')) {
            EmployeeSalaryHistory::create([
                'user_id' => $user->id,
                'old_salary' => null,
                'new_salary' => $request->salary,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'notes' => 'تحديد المرتب عند إنشاء الموظف',
            ]);
        }

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $userData = $user->load('branchAgent');
        
        // جلب العهد من نظام المخازن الجديد
        $inventoryCustodies = \App\Models\FixedCustody::with('item')
            ->where('recipient_id', $user->id)
            ->where('recipient_type', User::class)
            ->where('status', 'active')
            ->get();
        
        $newFixed = [];
        $newConsumed = [];
        
        foreach ($inventoryCustodies as $c) {
            $item = $c->item;
            $formatted = [
                'description' => $item ? $item->name : 'صنف غير معروف',
                'quantity' => $c->quantity,
                'is_inventory' => true
            ];
            
            if ($item && $item->inventory_type === 'fixed') {
                $newFixed[] = $formatted;
            } else {
                $newConsumed[] = $formatted;
            }
        }
        
        // تحويل الموديل إلى array لتمكين التعديل على البيانات المرسلة
        $response = $userData->toArray();
        
        // دمج العهد القديمة مع العهد الجديدة
        $currentFixed = isset($response['fixed_custodies']) && is_array($response['fixed_custodies']) ? $response['fixed_custodies'] : [];
        $currentConsumed = isset($response['consumed_custodies']) && is_array($response['consumed_custodies']) ? $response['consumed_custodies'] : [];
        
        $response['fixed_custodies'] = array_merge($currentFixed, $newFixed);
        $response['consumed_custodies'] = array_merge($currentConsumed, $newConsumed);
        
        return response()->json($response);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id,
            'name' => 'required|string',
            'email' => 'nullable|email',
            'password' => 'nullable|string|min:6',
            'is_admin' => 'nullable|boolean',
            'authorized_documents' => 'nullable|array',
            'salary' => 'nullable|numeric',
            'national_id_number' => 'nullable|string|max:64',
            'job_title' => 'nullable|string|max:191',
            // الموظفين
            'full_name_quad' => 'nullable|string',
            'mother_name' => 'nullable|string',
            'gender' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string',
            'nationality' => 'nullable|string',
            'social_status' => 'nullable|string',
            'qualification' => 'nullable|string',
            'blood_type' => 'nullable|string',
            'personal_phone' => 'nullable|string',
            'guardian_phone' => 'nullable|string',
            'address' => 'nullable|string',
            'financial_number' => 'nullable|string',
            'job_number' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'bank_branch' => 'nullable|string',
            'account_number' => 'nullable|string',
            'start_date' => 'nullable|date',
            'working_hours_from' => 'nullable|string',
            'working_hours_to' => 'nullable|string',
            'working_days_from' => 'nullable|string',
            'working_days_to' => 'nullable|string',
            'contract_type' => 'nullable|string',
            'contract_duration' => 'nullable|string',
            'contract_conditions' => 'nullable|string',
            'housing_allowance' => 'nullable|numeric',
            'transportation_allowance' => 'nullable|numeric',
            'communication_allowance' => 'nullable|numeric',
            'fixed_bonuses' => 'nullable|numeric',
            'fixed_fines' => 'nullable|numeric',
            'hourly_leave_deduction' => 'nullable|numeric',
            'daily_leave_deduction' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'show_on_landing' => 'nullable|boolean',
            'tax_percentage' => 'nullable|numeric',
            'social_security_percentage' => 'nullable|numeric',
            'salary_type' => 'nullable|string|in:monthly,hourly',
            'hourly_rate' => 'nullable|numeric',
            'eidc_username' => 'nullable|string|max:191',
            'eidc_password' => 'nullable|string|max:191',
            'lifo_username' => 'nullable|string|max:191',
            'lifo_password' => 'nullable|string|max:191',
            'lifo_office_id' => 'nullable|string|max:191',
            'apply_tax' => 'nullable|boolean',
            'apply_social_security' => 'nullable|boolean',
            'tax_file_number' => 'nullable|string|max:191',
            'social_security_file_number' => 'nullable|string|max:191',
            'end_date' => 'nullable|date',
        ]);

        $oldSalary = $user->salary;

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
            $user->tokens()->delete();
        } else {
            unset($validated['password']);
        }

        if ($request->has('is_admin')) {
            if ($request->is_admin) {
                $validated['authorized_documents'] = null;
            }
        }

        $user->update($validated);

        if ((string) ($oldSalary ?? '') !== (string) ($user->salary ?? '')) {
            EmployeeSalaryHistory::create([
                'user_id' => $user->id,
                'old_salary' => $oldSalary,
                'new_salary' => $user->salary,
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'notes' => 'تعديل قيمة المرتب',
            ]);
        }

        return response()->json($user);
    }

    public function salaryHistory(User $user)
    {
        $history = EmployeeSalaryHistory::with('changedBy:id,name')
            ->where('user_id', $user->id)
            ->orderByDesc('changed_at')
            ->limit(100)
            ->get();

        return response()->json($history);
    }

    public function destroy(User $user)
    {
        Storage::disk('public')->deleteDirectory('users/' . $user->id);
        $user->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Upload profile photo, personal ID proof, or employment contract (multipart).
     */
    public function uploadEmployeeFile(Request $request, User $user)
    {
        $allowedTypes = [
            'profile_photo',
            'personal_id_proof',
            'employment_contract',
            'national_id_photo',
            'identity_proof',
            'certified_stamp',
            'approved_signature',
            'educational_certificate',
            'health_certificate',
            'contract_conditions_photo',
            'passport_photo',
            'clearance_certificate',
            'experience_certificate',
            'work_commencement_order',
            'resignation_letter',
            'other'
        ];

        $request->validate([
            'type' => 'required|in:' . implode(',', $allowedTypes),
            'file' => 'required|file|max:10240',
        ]);

        $type = $request->input('type');
        $file = $request->file('file');

        $allowedImages = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = $file->getMimeType();

        // الصور فقط لبعض الأنواع
        $imageOnlyTypes = ['profile_photo', 'certified_stamp', 'approved_signature'];
        if (in_array($type, $imageOnlyTypes) && !in_array($mime, $allowedImages, true)) {
            return response()->json(['message' => 'هذا الملف يجب أن يكون صورة بصيغة JPEG أو PNG أو WEBP'], 422);
        }

        // الصور والـ PDF للبقية
        if (!in_array($type, $imageOnlyTypes) && !in_array($mime, array_merge($allowedImages, ['application/pdf']), true)) {
            return response()->json(['message' => 'الملف يجب أن يكون صورة (JPEG/PNG/WEBP) أو PDF'], 422);
        }

        $dir = 'users/' . $user->id;
        Storage::disk('public')->makeDirectory($dir);

        $attr = match ($type) {
            'profile_photo' => 'profile_photo_path',
            'personal_id_proof' => 'personal_id_proof_path',
            'employment_contract' => 'employment_contract_path',
            'national_id_photo' => 'national_id_photo_path',
            'identity_proof' => 'identity_proof_path',
            'certified_stamp' => 'certified_stamp_path',
            'approved_signature' => 'approved_signature_path',
            'educational_certificate' => 'educational_certificate_path',
            'health_certificate' => 'health_certificate_path',
            'contract_conditions_photo' => 'contract_conditions_photo_path',
            'passport_photo' => 'passport_photo_path',
            'clearance_certificate' => 'clearance_certificate_path',
            'experience_certificate' => 'experience_certificate_path',
            'work_commencement_order' => 'work_commencement_order_path',
            'resignation_letter' => 'resignation_letter_path',
            default => null,
        };

        // If 'other', we might need a different handling or it's just saved without an attribute for now
        if (!$attr) {
            return response()->json(['message' => 'نوع الملف غير مدعوم للحفظ في الحساب حالياً'], 422);
        }

        $oldPath = $user->{$attr};
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = $type . '_' . time() . '.' . $ext;

        $storedPath = $file->storeAs($dir, $filename, 'public');
        $user->{$attr} = $storedPath;
        $user->save();

        return response()->json([
            'message' => 'تم رفع الملف بنجاح',
            'type' => $type,
            'url' => $user->{$type . '_url'} ?? '/storage/' . $storedPath,
        ]);
    }

    public function updateEmail(Request $request, User $user)
    {
        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->email = $request->email;
        $user->save();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
            'confirm_password' => 'required|string|same:new_password',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'كلمة المرور الحالية غير صحيحة'], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->tokens()->delete();
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'تم تحديث كلمة المرور بنجاح وتسجيل الخروج من جميع الأجهزة الأخرى',
            'token' => $token,
        ]);
    }

    public function updateEidcCredentials(Request $request, User $user)
    {
        $request->validate([
            'eidc_username' => 'required|string|max:191',
            'eidc_password' => 'required|string|max:191',
        ]);

        $user->eidc_username = $request->eidc_username;
        $user->eidc_password = $request->eidc_password;
        $user->save();

        return response()->json([
            'message' => 'تم تحديث بيانات الهيئة بنجاح',
            'eidc_username' => $user->eidc_username
        ]);
    }

    public function updateLifoCredentials(Request $request, User $user)
    {
        $request->validate([
            'lifo_username' => 'required|string|max:191',
            'lifo_password' => 'required|string|max:191',
            'lifo_office_id' => 'nullable|string|max:191',
        ]);

        $user->lifo_username = $request->lifo_username;
        $user->lifo_password = $request->lifo_password;
        $user->lifo_office_id = $request->lifo_office_id;
        $user->save();

        return response()->json([
            'message' => 'تم تحديث بيانات الاتحاد بنجاح',
            'lifo_username' => $user->lifo_username,
            'lifo_office_id' => $user->lifo_office_id,
        ]);
    }

    public function getGeneralManager()
    {
        try {
            $gm = User::where('job_title', 'like', '%المدير العام%')
                ->orWhere('job_title', 'like', '%مدير عام%')
                ->first();

            if (!$gm) {
                // fallback to the first admin
                $gm = User::where('is_admin', true)->first();
            }

            if (!$gm) {
                return response()->json([
                    'name' => null,
                    'job_title' => null,
                    'approved_signature_url' => null,
                    'certified_stamp_url' => null,
                ]);
            }

            return response()->json([
                'id' => $gm->id,
                'name' => $gm->name,
                'job_title' => $gm->job_title,
                'approved_signature_url' => $gm->approved_signature_url,
                'certified_stamp_url' => $gm->certified_stamp_url,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب بيانات المدير العام',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    public function publicEmployees()
    {
        try {
            $employees = User::whereDoesntHave('branchAgent')
                ->where('is_admin', false)
                ->where('is_active', true)
                ->where('show_on_landing', true)
                ->whereNull('department_id')
                ->orderByRaw('CASE WHEN start_date IS NULL THEN 1 ELSE 0 END')
                ->orderBy('start_date', 'asc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'job_title' => $user->job_title,
                        'profile_photo_url' => $user->profile_photo_url,
                        'gender' => $user->gender,
                        'nationality' => $user->nationality,
                        'personal_phone' => $user->personal_phone,
                    ];
                });

            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الموظفين',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    public function allEmployees()
    {
        try {
            $employees = User::whereDoesntHave('branchAgent')
                ->where('is_active', true)
                ->orderBy('name', 'asc')
                ->get(['id', 'name', 'job_title', 'department_id']);

            return response()->json($employees);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الموظفين',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    public function toggleShowOnLanding($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->show_on_landing = !$user->show_on_landing;
            $user->save();

            return response()->json([
                'success' => true,
                'show_on_landing' => $user->show_on_landing,
                'message' => $user->show_on_landing ? 'سيظهر الموظف في الواجهة الرئيسية' : 'تم إخفاء الموظف من الواجهة الرئيسية'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث حالة الظهور في الواجهة',
                'error' => config('app.debug') ? $e->getMessage() : 'خطأ غير معروف'
            ], 500);
        }
    }

    public function officeUsersIndex(Request $request)
    {
        $agentId = $request->user()->branch_agent_id ?? (\App\Models\BranchAgent::where('user_id', $request->user()->id)->value('id'));
        if (!$agentId) {
            return response()->json(['message' => 'غير مصرح للقيام بهذه العملية'], 403);
        }

        $users = User::where('branch_agent_id', $agentId)
            ->where('id', '!=', $request->user()->id)
            ->get(['id', 'username', 'name', 'lifo_username', 'lifo_permissions', 'lifo_user_id', 'is_active']);

        return response()->json($users);
    }

    public function storeOfficeUser(Request $request)
    {
        $agentUser = $request->user();
        $branchAgent = $agentUser->branchAgent ?? \App\Models\BranchAgent::where('user_id', $agentUser->id)->first();

        if (!$branchAgent) {
            return response()->json(['message' => 'هذا الحساب غير مرتبط بوكيل'], 403);
        }

        $lifoUsername = $agentUser->lifo_username;
        $lifoPassword = $agentUser->lifo_password;
        $lifoOfficeId = $agentUser->lifo_office_id;

        if (!$lifoUsername || !$lifoPassword || !$lifoOfficeId) {
            return response()->json(['message' => 'يرجى تهيئة بيانات اعتمادات الاتحاد (LIFO) للوكيل أولاً'], 400);
        }

        $request->validate([
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
            'permissions' => 'required|array',
            'permissions.*' => 'integer|in:1,2,3',
        ]);

        try {
            $postParams = [
                'user_name' => $lifoUsername,
                'pass_word' => $lifoPassword,
                'username' => $request->username,
                'password' => $request->password,
                'password_confirmation' => $request->password,
            ];

            foreach ($request->permissions as $index => $permissionId) {
                $postParams["permisson[$index]"] = $permissionId;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withoutVerifying()
                ->asForm()
                ->post("https://prodapi.lifo.ly/api/offices/addofficeuser/{$lifoOfficeId}", $postParams);

            if ($response->failed() || $response->json('code') !== 1) {
                $errorMsg = $response->json('message') ?? $response->json('messages') ?? 'فشل إضافة المستخدم في نظام الاتحاد';
                if (is_array($errorMsg)) {
                    $errorMsg = implode(', ', $errorMsg);
                }
                return response()->json(['message' => 'خطأ من الاتحاد: ' . $errorMsg], 400);
            }

            $lifoResponseData = $response->json('data') ?? [];
            \Illuminate\Support\Facades\Log::info('LIFO office user creation success', ['response' => $lifoResponseData]);

            // Try to extract user ID from LIFO response
            $lifoUserId = null;
            if (is_array($lifoResponseData)) {
                if (isset($lifoResponseData['id'])) {
                    $lifoUserId = $lifoResponseData['id'];
                } else {
                    $matched = collect($lifoResponseData)->first(function($u) use ($request) {
                        return strtolower($u['username'] ?? '') === strtolower($request->username);
                    });
                    if ($matched) {
                        $lifoUserId = $matched['id'] ?? null;
                    }
                }
            }

            // Create local user
            $user = User::create([
                'username' => $request->username,
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'branch_agent_id' => $branchAgent->id,
                'lifo_username' => $request->username,
                'lifo_password' => $request->password,
                'lifo_office_id' => $lifoOfficeId,
                'lifo_permissions' => $request->permissions,
                'lifo_user_id' => $lifoUserId ? (string)$lifoUserId : null,
                'authorized_documents' => ['تأمين سيارات دولي'],
                'is_active' => true,
            ]);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Office sub-user creation failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ غير متوقع أثناء إضافة المستخدم: ' . $e->getMessage()], 500);
        }
    }

    public function toggleOfficeUserStatus(Request $request, $id)
    {
        $agentUser = $request->user();
        $branchAgent = $agentUser->branchAgent ?? \App\Models\BranchAgent::where('user_id', $agentUser->id)->first();

        if (!$branchAgent) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $user = User::where('branch_agent_id', $branchAgent->id)->findOrFail($id);
        
        $lifoUsername = $agentUser->lifo_username;
        $lifoPassword = $agentUser->lifo_password;
        $lifoUserId = $user->lifo_user_id;

        if (!$lifoUserId) {
            $user->is_active = !$user->is_active;
            $user->save();
            return response()->json([
                'success' => true,
                'is_active' => $user->is_active,
                'message' => 'تم تغيير حالة المستخدم محلياً فقط لعدم وجود معرّف الاتحاد للمستخدم.'
            ]);
        }

        $newStatus = !$user->is_active;
        $endpoint = $newStatus 
            ? "https://prodapi.lifo.ly/api/offices/activationAccount/{$lifoUserId}" 
            : "https://prodapi.lifo.ly/api/offices/disableAccount/{$lifoUserId}";

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)
                ->withoutVerifying()
                ->asForm()
                ->post($endpoint, [
                    'user_name' => $lifoUsername,
                    'pass_word' => $lifoPassword,
                ]);

            if ($response->failed() || $response->json('code') !== 1) {
                $errorMsg = $response->json('message') ?? $response->json('messages') ?? 'فشل تحديث الحالة في نظام الاتحاد';
                if (is_array($errorMsg)) {
                    $errorMsg = implode(', ', $errorMsg);
                }
                return response()->json(['message' => 'خطأ من الاتحاد: ' . $errorMsg], 400);
            }

            $user->is_active = $newStatus;
            if (!$newStatus) {
                $user->tokens()->delete();
            }
            $user->save();

            return response()->json([
                'success' => true,
                'is_active' => $user->is_active,
                'message' => $newStatus ? 'تم تفعيل حساب المستخدم بنجاح' : 'تم تعطيل حساب المستخدم بنجاح'
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Office sub-user toggle status failed: ' . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ في الاتصال بسيرفر الاتحاد: ' . $e->getMessage()], 500);
        }
    }

    public function updateOfficeUser(Request $request, $id)
    {
        $agentUser = $request->user();
        $branchAgent = $agentUser->branchAgent ?? \App\Models\BranchAgent::where('user_id', $agentUser->id)->first();

        if (!$branchAgent) {
            return response()->json(['message' => 'غير مصرح للقيام بهذه العملية'], 403);
        }

        $user = User::where('branch_agent_id', $branchAgent->id)->findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'password' => 'nullable|string|min:6|confirmed',
            'permissions' => 'required|array',
            'permissions.*' => 'integer|in:1,2,3',
        ]);

        $user->name = $request->name;
        $user->lifo_permissions = $request->permissions;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
            $user->lifo_password = $request->password;
            $user->tokens()->delete();
        }

        $user->save();

        try {
            $this->syncOfficeUserPermissionsAndPwdToLifo(
                $agentUser,
                $user->username,
                $request->permissions,
                $request->filled('password') ? $request->password : null
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('LIFO sync failed: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'name' => $user->name,
                    'lifo_username' => $user->lifo_username,
                    'lifo_permissions' => $user->lifo_permissions,
                    'lifo_user_id' => $user->lifo_user_id,
                    'is_active' => $user->is_active,
                ],
                'message' => 'تم تحديث البيانات محلياً، ولكن فشل التزامن مع الاتحاد: ' . $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'lifo_username' => $user->lifo_username,
                'lifo_permissions' => $user->lifo_permissions,
                'lifo_user_id' => $user->lifo_user_id,
                'is_active' => $user->is_active,
            ],
            'message' => 'تم تحديث بيانات الموظف وصلاحياته ومزامنتها مع الاتحاد بنجاح.'
        ]);
    }

    public function destroyOfficeUser(Request $request, $id)
    {
        $agentUser = $request->user();
        $branchAgent = $agentUser->branchAgent ?? \App\Models\BranchAgent::where('user_id', $agentUser->id)->first();

        if (!$branchAgent) {
            return response()->json(['message' => 'غير مصرح'], 403);
        }

        $user = User::where('branch_agent_id', $branchAgent->id)->findOrFail($id);
        
        $lifoUsername = $agentUser->lifo_username;
        $lifoPassword = $agentUser->lifo_password;
        $lifoUserId = $user->lifo_user_id;

        if ($lifoUserId) {
            try {
                \Illuminate\Support\Facades\Http::timeout(20)
                    ->withoutVerifying()
                    ->asForm()
                    ->post("https://prodapi.lifo.ly/api/offices/disableAccount/{$lifoUserId}", [
                        'user_name' => $lifoUsername,
                        'pass_word' => $lifoPassword,
                    ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('LIFO disable user failed during deletion: ' . $e->getMessage());
            }
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف مستخدم المكتب بنجاح'
        ]);
    }

    /**
     * Synchronize sub-user permissions and password to LIFO web portal via Guzzle.
     */
    private function syncOfficeUserPermissionsAndPwdToLifo($agentUser, $subUserUsername, $targetPermissions, $newPassword = null)
    {
        $lifoUsername = $agentUser->lifo_username;
        $lifoPassword = $agentUser->lifo_password;

        if (!$lifoUsername || !$lifoPassword) {
            throw new \Exception('اعتمادات الاتحاد للوكيل غير مهيأة.');
        }

        $cookieJar = new \GuzzleHttp\Cookie\CookieJar();
        $client = new \GuzzleHttp\Client([
            'verify'          => false,
            'timeout'         => 25.0,
            'connect_timeout' => 5.0,
            'cookies'         => $cookieJar,
            'allow_redirects' => [
                'max'             => 5,
                'strict'          => false,
                'referer'         => true,
                'track_redirects' => true
            ]
        ]);

        // 1. GET login page to obtain CSRF token
        $response = $client->request('GET', 'https://prod.lifo.ly/office/login');
        $html = $response->getBody()->getContents();
        
        preg_match('/name="_token"\s+value="([^"]+)"/', $html, $matches);
        $token = $matches[1] ?? null;
        if (!$token) {
            preg_match('/csrf-token"\s+content="([^"]+)"/', $html, $matches);
            $token = $matches[1] ?? null;
        }
        
        if (!$token) {
            throw new \Exception('فشل الحصول على توكن التحقق (CSRF) من الاتحاد.');
        }

        // 2. POST login request
        $client->request('POST', 'https://prod.lifo.ly/office/login', [
            'form_params' => [
                '_token' => $token,
                'username' => $lifoUsername,
                'password' => $lifoPassword,
            ],
            'headers' => [
                'Referer' => 'https://prod.lifo.ly/office/login',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9',
            ]
        ]);

        // 3. Fetching users list via AJAX to get URLs
        $response = $client->request('GET', 'https://prod.lifo.ly/office/offices_users/offices_users', [
            'headers' => [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
                'Referer' => 'https://prod.lifo.ly/office/offices_users',
            ]
        ]);
        
        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);
        
        $matchedUser = null;
        if (isset($data['data'])) {
            foreach ($data['data'] as $u) {
                if (strtolower($u['username'] ?? '') === strtolower($subUserUsername)) {
                    $matchedUser = $u;
                    break;
                }
            }
        }
        
        if (!$matchedUser) {
            throw new \Exception("المستخدم '$subUserUsername' غير موجود في حساب الاتحاد الخاص بكم.");
        }

        // 4. Permissions Syncing
        if (isset($matchedUser['showpermission']) && preg_match('/href="([^"]+)"/', $matchedUser['showpermission'], $permMatches)) {
            $permUrl = $permMatches[1];
            $permResponse = $client->request('GET', $permUrl);
            $permHtml = $permResponse->getBody()->getContents();
            
            // Find all permission rows in showpermission page
            preg_match_all('/<tr>\s*<td>\s*(.*?)\s*<\/td>\s*<td>.*?<form[^>]+action="([^"]+deletePermission\/(\d+))"[^>]*>(.*?)<\/form>/is', $permHtml, $rows, PREG_SET_ORDER);
            
            $existingLifoPerms = [];
            $permissionMap = [
                'صلاحية عرض البطاقات' => 1,
                'صلاحية اصدار وثيقة'  => 2,
                'صلاحية ادارة التقارير' => 3
            ];

            foreach ($rows as $row) {
                $permName = trim(strip_tags($row[1]));
                $deleteUrl = $row[2];
                $permUserId = $row[3];
                $formContent = $row[4];
                
                $localId = $permissionMap[$permName] ?? null;
                if ($localId) {
                    preg_match('/name="_token"\s+value="([^"]+)"/', $formContent, $tokenMatches);
                    $formCsrf = $tokenMatches[1] ?? null;
                    
                    $existingLifoPerms[$localId] = [
                        'delete_url' => $deleteUrl,
                        'csrf_token' => $formCsrf,
                        'perm_user_id' => $permUserId
                    ];
                }
            }

            // A. Delete permissions not in target
            foreach ($existingLifoPerms as $permId => $info) {
                if (!in_array($permId, $targetPermissions)) {
                    if ($info['csrf_token']) {
                        $client->request('POST', $info['delete_url'], [
                            'form_params' => [
                                '_token' => $info['csrf_token'],
                                '_method' => 'DELETE'
                            ],
                            'headers' => [
                                'Referer' => $permUrl,
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                            ]
                        ]);
                    }
                }
            }

            // B. Add permissions not currently in LIFO
            $permsToAdd = [];
            foreach ($targetPermissions as $p) {
                if (!isset($existingLifoPerms[$p])) {
                    $permsToAdd[] = $p;
                }
            }

            if (!empty($permsToAdd) && isset($matchedUser['edit']) && preg_match('/href="([^"]+)"/', $matchedUser['edit'], $editMatches)) {
                $editUrl = $editMatches[1];
                $editResponse = $client->request('GET', $editUrl);
                $editHtml = $editResponse->getBody()->getContents();
                
                preg_match('/name="_token"\s+value="([^"]+)"/', $editHtml, $editTokenMatches);
                $editCsrfToken = $editTokenMatches[1] ?? null;
                if (!$editCsrfToken) {
                    preg_match('/csrf-token"\s+content="([^"]+)"/', $editHtml, $editTokenMatches);
                    $editCsrfToken = $editTokenMatches[1] ?? null;
                }

                if ($editCsrfToken) {
                    $bodyStr = http_build_query([
                        '_token' => $editCsrfToken,
                        'username' => $subUserUsername
                    ]);
                    foreach ($permsToAdd as $p) {
                        $bodyStr .= '&permisson[]=' . $p;
                    }
                    
                    $client->request('POST', $editUrl, [
                        'body' => $bodyStr,
                        'headers' => [
                            'Content-Type' => 'application/x-www-form-urlencoded',
                            'Referer' => $editUrl,
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                        ]
                    ]);
                }
            }
        }

        // 5. Password Syncing
        if ($newPassword && isset($matchedUser['changepassord']) && preg_match('/href="([^"]+)"/', $matchedUser['changepassord'], $pwdMatches)) {
            $pwdUrl = $pwdMatches[1];
            $pwdResponse = $client->request('GET', $pwdUrl);
            $pwdHtml = $pwdResponse->getBody()->getContents();
            
            preg_match('/name="_token"\s+value="([^"]+)"/', $pwdHtml, $pwdTokenMatches);
            $pwdCsrfToken = $pwdTokenMatches[1] ?? null;
            if (!$pwdCsrfToken) {
                preg_match('/csrf-token"\s+content="([^"]+)"/', $pwdHtml, $pwdTokenMatches);
                $pwdCsrfToken = $pwdTokenMatches[1] ?? null;
            }

            if ($pwdCsrfToken) {
                $client->request('POST', $pwdUrl, [
                    'form_params' => [
                        '_token' => $pwdCsrfToken,
                        'new-password' => $newPassword,
                        'new-password-confirm' => $newPassword,
                    ],
                    'headers' => [
                        'Referer' => $pwdUrl,
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    ]
                ]);
            }
        }
    }
}

