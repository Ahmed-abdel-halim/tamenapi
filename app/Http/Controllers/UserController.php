<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        
        $query = User::with('branchAgent:id,user_id,type,agency_name,agent_name')
            ->select('id', 'username', 'name', 'email', 'is_admin', 'authorized_documents');

        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $userData = [
                'id' => $user->id,
                'username' => $user->username,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin ?? false,
                'authorized_documents' => $user->authorized_documents ?? [],
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
            'authorized_documents' => $request->is_admin ? null : $authorizedDocuments, // Admin لا يحتاج صلاحيات
        ]);
        
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
        ]);
        
        $user->username = $request->username;
        $user->name = $request->name;
        $user->email = $request->email;
        
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
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['status' => 'deleted']);
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
