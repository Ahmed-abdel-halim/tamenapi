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
        $perPage = $request->get('per_page', 10);
        
        $query = User::with('branchAgent:id,user_id,type,agency_name,agent_name');

        // استبعاد أي مستخدم مرتبط ببيانات وكيل أو فرع من قائمة الموظفين
        $query->whereDoesntHave('branchAgent');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
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
            'contract_conditions' => 'nullable|string',
            'housing_allowance' => 'nullable|numeric',
            'transportation_allowance' => 'nullable|numeric',
            'communication_allowance' => 'nullable|numeric',
            'fixed_bonuses' => 'nullable|numeric',
            'fixed_fines' => 'nullable|numeric',
            'hourly_leave_deduction' => 'nullable|numeric',
            'daily_leave_deduction' => 'nullable|numeric',
        ]);

        $data = $validated;
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
        return response()->json($user->load('branchAgent'));
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
            'contract_conditions' => 'nullable|string',
            'housing_allowance' => 'nullable|numeric',
            'transportation_allowance' => 'nullable|numeric',
            'communication_allowance' => 'nullable|numeric',
            'fixed_bonuses' => 'nullable|numeric',
            'fixed_fines' => 'nullable|numeric',
            'hourly_leave_deduction' => 'nullable|numeric',
            'daily_leave_deduction' => 'nullable|numeric',
        ]);
        
        $oldSalary = $user->salary;
        
        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
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
        Storage::disk('public')->deleteDirectory('users/'.$user->id);
        $user->delete();

        return response()->json(['status' => 'deleted']);
    }

    /**
     * Upload profile photo, personal ID proof, or employment contract (multipart).
     */
    public function uploadEmployeeFile(Request $request, User $user)
    {
        $allowedTypes = [
            'profile_photo', 'personal_id_proof', 'employment_contract', 
            'national_id_photo', 'identity_proof', 'certified_stamp', 
            'approved_signature', 'educational_certificate', 'health_certificate', 
            'contract_conditions_photo', 'other'
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
        if (in_array($type, $imageOnlyTypes) && ! in_array($mime, $allowedImages, true)) {
            return response()->json(['message' => 'هذا الملف يجب أن يكون صورة بصيغة JPEG أو PNG أو WEBP'], 422);
        }

        // الصور والـ PDF للبقية
        if (! in_array($type, $imageOnlyTypes) && ! in_array($mime, array_merge($allowedImages, ['application/pdf']), true)) {
            return response()->json(['message' => 'الملف يجب أن يكون صورة (JPEG/PNG/WEBP) أو PDF'], 422);
        }

        $dir = 'users/'.$user->id;
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
            'url' => $user->{$type . '_url'} ?? '/storage/'.$storedPath,
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
        $user->save();

        return response()->json(['message' => 'تم تحديث كلمة المرور بنجاح']);
    }
}
