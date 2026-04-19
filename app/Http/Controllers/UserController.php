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
        
        $query = User::with('branchAgent:id,user_id,type,agency_name,agent_name')
            ->select(
                'id',
                'username',
                'name',
                'email',
                'is_admin',
                'authorized_documents',
                'salary',
                'national_id_number',
                'job_title',
                'profile_photo_path',
                'personal_id_proof_path',
                'employment_contract_path'
            );

        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin ?? false,
                'authorized_documents' => $user->authorized_documents ?? [],
                'salary' => $user->salary,
                'national_id_number' => $user->national_id_number,
                'job_title' => $user->job_title,
                'profile_photo_url' => $user->profile_photo_url,
                'personal_id_proof_url' => $user->personal_id_proof_url,
                'employment_contract_url' => $user->employment_contract_url,
                'user_type' => 'مستخدم عادي',
                'branch_agent_info' => null,
            ];

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
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'name' => 'required|string',
            'email' => 'nullable|email',
            'password' => 'required|string|min:6',
            'is_admin' => 'nullable|boolean',
            'authorized_documents' => 'nullable|array',
            'salary' => 'nullable|numeric',
            'national_id_number' => 'nullable|string|max:64',
            'job_title' => 'nullable|string|max:191',
        ]);

        $authorizedDocuments = $request->has('authorized_documents') 
            ? $request->authorized_documents 
            : [];

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->is_admin ?? false,
            'authorized_documents' => $request->is_admin ? null : $authorizedDocuments,
            'salary' => $request->salary,
            'national_id_number' => $request->national_id_number,
            'job_title' => $request->job_title,
        ]);

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
        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin ?? false,
            'authorized_documents' => $user->authorized_documents ?? [],
            'salary' => $user->salary,
            'national_id_number' => $user->national_id_number,
            'job_title' => $user->job_title,
            'profile_photo_url' => $user->profile_photo_url,
            'personal_id_proof_url' => $user->personal_id_proof_url,
            'employment_contract_url' => $user->employment_contract_url,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username,' . $user->id,
            'name' => 'required|string',
            'email' => 'nullable|email',
            'password' => 'nullable|string|min:6',
            'is_admin' => 'nullable|boolean',
            'authorized_documents' => 'nullable|array',
            'salary' => 'nullable|numeric',
            'national_id_number' => 'nullable|string|max:64',
            'job_title' => 'nullable|string|max:191',
        ]);
        
        $user->username = $request->username;
        $user->name = $request->name;
        $user->email = $request->email;
        $oldSalary = $user->salary;
        $user->salary = $request->salary;
        $user->national_id_number = $request->national_id_number;
        $user->job_title = $request->job_title;
        
        if ($request->has('is_admin')) {
            $user->is_admin = $request->is_admin;
            // إذا أصبح المستخدم admin، احذف الصلاحيات
            if ($request->is_admin) {
                $user->authorized_documents = null;
            }
        }
        
        // تحديث الصلاحيات فقط إذا لم يكن admin
        if ($request->has('authorized_documents') && !($request->is_admin ?? $user->is_admin)) {
            $user->authorized_documents = $request->authorized_documents;
        }
        
        if ($request->password) {
            $user->password = Hash::make($request->password);
        }
        
        $user->save();

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
        $request->validate([
            'type' => 'required|in:profile_photo,personal_id_proof,employment_contract',
            'file' => 'required|file|max:10240',
        ]);

        $type = $request->input('type');
        $file = $request->file('file');

        $allowedImages = ['image/jpeg', 'image/png', 'image/webp'];
        $mime = $file->getMimeType();

        if ($type === 'profile_photo' && ! in_array($mime, $allowedImages, true)) {
            return response()->json(['message' => 'الصورة الشخصية يجب أن تكون بصيغة JPEG أو PNG أو WEBP'], 422);
        }

        if ($type !== 'profile_photo' && ! in_array($mime, array_merge($allowedImages, ['application/pdf']), true)) {
            return response()->json(['message' => 'الملف يجب أن يكون صورة (JPEG/PNG/WEBP) أو PDF'], 422);
        }

        $dir = 'users/'.$user->id;
        Storage::disk('public')->makeDirectory($dir);

        $attr = match ($type) {
            'profile_photo' => 'profile_photo_path',
            'personal_id_proof' => 'personal_id_proof_path',
            default => 'employment_contract_path',
        };

        $oldPath = $user->{$attr};
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $basename = match ($type) {
            'profile_photo' => 'profile_photo',
            'personal_id_proof' => 'personal_id_proof',
            default => 'employment_contract',
        };
        $filename = $basename.'.'.$ext;

        $storedPath = $file->storeAs($dir, $filename, 'public');
        $user->{$attr} = $storedPath;
        $user->save();

        $publicUrl = '/storage/'.str_replace('\\', '/', ltrim($storedPath, '/'));

        return response()->json([
            'message' => 'تم رفع الملف بنجاح',
            'type' => $type,
            'url' => $publicUrl,
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
