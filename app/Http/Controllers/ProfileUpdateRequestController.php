<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ProfileUpdateRequest;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileUpdateRequestController extends Controller
{
    /**
     * Get a list of pending/all profile update requests for admins.
     */
    public function index(Request $request)
    {
        // Only admins can see this list
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذا الإجراء'], 403);
        }

        $status = $request->query('status', 'pending');
        $type = $request->query('type'); // 'agent' or 'employee'
        
        $query = ProfileUpdateRequest::with('user.branchAgent')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type === 'agent') {
            $query->whereHas('user.branchAgent');
        } elseif ($type === 'employee') {
            $query->whereDoesntHave('user.branchAgent');
        }

        $requests = $query->paginate(20);

        // Add additional current details for visual comparison on frontend
        $requests->getCollection()->transform(function ($req) {
            $user = $req->user;
            
            // Format requested paths into full storage URLs if they exist
            $changes = $req->requested_changes;
            $fileFields = [
                'profile_photo_path' => 'profile_photo_url',
                'passport_photo_path' => 'passport_photo_url',
                'identity_proof_path' => 'identity_proof_url',
                'national_id_photo_path' => 'national_id_photo_url',
                'contract_photo_path' => 'contract_photo_url',
                'clearance_certificate_path' => 'clearance_certificate_url',
                'non_bankruptcy_certificate_path' => 'non_bankruptcy_certificate_url',
                'experience_certificate_path' => 'experience_certificate_url',
                'non_employment_certificate_path' => 'non_employment_certificate_url',
                'tb_health_certificate_path' => 'tb_health_certificate_url',
                'academic_qualification_path' => 'academic_qualification_url',
                'activity_license_path' => 'activity_license_url',
            ];
            foreach ($fileFields as $pathKey => $urlKey) {
                if (isset($changes[$pathKey])) {
                    $changes[$urlKey] = '/storage/' . $changes[$pathKey];
                }
            }

            return [
                'id' => $req->id,
                'user_id' => $req->user_id,
                'status' => $req->status,
                'admin_notes' => $req->admin_notes,
                'processed_at' => $req->processed_at,
                'created_at' => $req->created_at,
                'requested_changes' => $changes,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'personal_phone' => $user->personal_phone,
                    'profile_photo_url' => $user->profile_photo_url,
                    'passport_photo_url' => $user->passport_photo_url,
                    'identity_proof_url' => $user->identity_proof_url,
                    'national_id_photo_url' => $user->national_id_photo_url,
                    'contract_photo_url' => $user->employment_contract_url,
                    'clearance_certificate_url' => $user->clearance_certificate_url,
                    'non_bankruptcy_certificate_url' => $user->branchAgent && $user->branchAgent->non_bankruptcy_certificate ? '/storage/' . $user->branchAgent->non_bankruptcy_certificate : null,
                    'experience_certificate_url' => $user->experience_certificate_url,
                    'non_employment_certificate_url' => $user->branchAgent && $user->branchAgent->non_employment_certificate ? '/storage/' . $user->branchAgent->non_employment_certificate : null,
                    'tb_health_certificate_url' => $user->health_certificate_url,
                    'academic_qualification_url' => $user->educational_certificate_url,
                    'activity_license_url' => $user->branchAgent && $user->branchAgent->activity_license ? '/storage/' . $user->branchAgent->activity_license : null,
                    'is_agent' => $user->branchAgent !== null,
                    'agent_info' => $user->branchAgent ? [
                        'id' => $user->branchAgent->id,
                        'agency_name' => $user->branchAgent->agency_name,
                        'agent_name' => $user->branchAgent->agent_name,
                    ] : null,
                ]
            ];
        });

        return response()->json($requests);
    }

    /**
     * Submit a new profile update request.
     */
    public function submit(Request $request)
    {
        $user = auth()->user();

        // Check if there is already a pending request
        $existing = ProfileUpdateRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'لديك طلب تعديل معلق بالفعل قيد المراجعة حالياً من قبل الإدارة'
            ], 400);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'personal_phone' => 'required|string|max:50',
            'profile_photo' => 'nullable|file|image|max:10240',
            'passport_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'identity_proof' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'national_id_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'contract_photo' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'clearance_certificate' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'non_bankruptcy_certificate' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'experience_certificate' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'non_employment_certificate' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'tb_health_certificate' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'academic_qualification' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'activity_license' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ]);

        $requestedChanges = [
            'name' => $request->input('name'),
            'personal_phone' => $request->input('personal_phone'),
        ];

        // Ensure temp storage directory exists
        Storage::disk('public')->makeDirectory('pending_profile_updates');

        $filesToUpload = [
            'profile_photo' => 'profile_photo_path',
            'passport_photo' => 'passport_photo_path',
            'identity_proof' => 'identity_proof_path',
            'national_id_photo' => 'national_id_photo_path',
            'contract_photo' => 'contract_photo_path',
            'clearance_certificate' => 'clearance_certificate_path',
            'non_bankruptcy_certificate' => 'non_bankruptcy_certificate_path',
            'experience_certificate' => 'experience_certificate_path',
            'non_employment_certificate' => 'non_employment_certificate_path',
            'tb_health_certificate' => 'tb_health_certificate_path',
            'academic_qualification' => 'academic_qualification_path',
            'activity_license' => 'activity_license_path',
        ];

        foreach ($filesToUpload as $inputKey => $storeKey) {
            if ($request->hasFile($inputKey)) {
                $file = $request->file($inputKey);
                $ext = strtolower($file->getClientOriginalExtension() ?: ($inputKey === 'profile_photo' ? 'png' : 'pdf'));
                $filename = $inputKey . '_' . $user->id . '_' . time() . '.' . $ext;
                $path = $file->storeAs('pending_profile_updates', $filename, 'public');
                $requestedChanges[$storeKey] = $path;
            }
        }

        // Create the request
        $profileUpdateRequest = ProfileUpdateRequest::create([
            'user_id' => $user->id,
            'requested_changes' => $requestedChanges,
            'status' => 'pending',
        ]);

        // Send system notification to all administrators
        $admins = User::where('is_admin', true)->get();
        foreach ($admins as $admin) {
            $admin->notify(new SystemNotification(
                'طلب تعديل بيانات الموظف/الوكيل',
                'قام الموظف/الوكيل (' . $user->name . ') بتقديم طلب لتعديل بياناته الشخصية ومستنداته.',
                'info',
                '/profile-update-requests'
            ));
        }

        return response()->json([
            'message' => 'تم تقديم طلب التعديل بنجاح وهو قيد المراجعة حالياً من قبل الإدارة',
            'data' => $profileUpdateRequest
        ]);
    }

    /**
     * Approve a profile update request.
     */
    public function approve($id)
    {
        // Only admins can approve
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذا الإجراء'], 403);
        }

        $profileRequest = ProfileUpdateRequest::findOrFail($id);

        if ($profileRequest->status !== 'pending') {
            return response()->json(['message' => 'هذا الطلب تم معالجته مسبقاً'], 400);
        }

        $user = User::findOrFail($profileRequest->user_id);
        $agent = $user->branchAgent;
        $changes = $profileRequest->requested_changes;

        // Ensure user final storage directory exists
        $userDir = 'users/' . $user->id;
        Storage::disk('public')->makeDirectory($userDir);

        $mapping = [
            'profile_photo_path' => [
                'user_field' => 'profile_photo_path',
                'agent_field' => 'personal_photo'
            ],
            'passport_photo_path' => [
                'user_field' => 'passport_photo_path',
                'agent_field' => 'passport_photo'
            ],
            'identity_proof_path' => [
                'user_field' => 'identity_proof_path',
                'agent_field' => 'identity_photo'
            ],
            'national_id_photo_path' => [
                'user_field' => 'national_id_photo_path',
                'agent_field' => 'national_id_photo'
            ],
            'contract_photo_path' => [
                'user_field' => 'employment_contract_path',
                'agent_field' => 'contract_photo'
            ],
            'clearance_certificate_path' => [
                'user_field' => 'clearance_certificate_path',
                'agent_field' => 'clearance_certificate'
            ],
            'non_bankruptcy_certificate_path' => [
                'user_field' => null,
                'agent_field' => 'non_bankruptcy_certificate'
            ],
            'experience_certificate_path' => [
                'user_field' => 'experience_certificate_path',
                'agent_field' => 'experience_certificate'
            ],
            'non_employment_certificate_path' => [
                'user_field' => null,
                'agent_field' => 'non_employment_certificate'
            ],
            'tb_health_certificate_path' => [
                'user_field' => 'health_certificate_path',
                'agent_field' => 'tb_health_certificate'
            ],
            'academic_qualification_path' => [
                'user_field' => 'educational_certificate_path',
                'agent_field' => 'academic_qualification'
            ],
            'activity_license_path' => [
                'user_field' => null,
                'agent_field' => 'activity_license'
            ],
        ];

        foreach ($mapping as $changeKey => $fields) {
            if (isset($changes[$changeKey])) {
                $tempPath = $changes[$changeKey];
                if (Storage::disk('public')->exists($tempPath)) {
                    $filename = basename($tempPath);
                    $newPath = $userDir . '/' . $filename;

                    // Delete old user file if exists
                    if ($fields['user_field'] && $user->{$fields['user_field']}) {
                        Storage::disk('public')->delete($user->{$fields['user_field']});
                    }
                    // Delete old agent file if exists
                    if ($agent && $fields['agent_field'] && $agent->{$fields['agent_field']}) {
                        if (!$fields['user_field'] || $agent->{$fields['agent_field']} !== $user->{$fields['user_field']}) {
                            Storage::disk('public')->delete($agent->{$fields['agent_field']});
                        }
                    }

                    Storage::disk('public')->move($tempPath, $newPath);

                    if ($fields['user_field']) {
                        $user->{$fields['user_field']} = $newPath;
                    }
                    if ($agent && $fields['agent_field']) {
                        $agent->{$fields['agent_field']} = $newPath;
                    }
                }
            }
        }

        // Update text fields
        if (isset($changes['name'])) {
            $user->name = $changes['name'];
            if ($agent) {
                $agent->agent_name = $changes['name'];
            }
        }

        if (isset($changes['personal_phone'])) {
            $user->personal_phone = $changes['personal_phone'];
            if ($agent) {
                $agent->phone = $changes['personal_phone'];
            }
        }

        // Save updates
        $user->save();
        if ($agent) {
            $agent->save();
        }

        // Update request status
        $profileRequest->status = 'approved';
        $profileRequest->processed_by = auth()->id();
        $profileRequest->processed_at = now();
        $profileRequest->save();

        // Notify the user
        $user->notify(new SystemNotification(
            'تمت الموافقة على تعديل بياناتك',
            'تمت الموافقة على طلبك لتحديث بيانات الملف الشخصي والمستندات بنجاح.',
            'success',
            '/profile'
        ));

        return response()->json([
            'message' => 'تمت الموافقة على الطلب وتحديث بيانات الملف الشخصي بنجاح',
            'data' => $profileRequest
        ]);
    }

    /**
     * Reject a profile update request.
     */
    public function reject(Request $request, $id)
    {
        // Only admins can reject
        if (!auth()->user()->is_admin) {
            return response()->json(['message' => 'غير مصرح لك بالوصول لهذا الإجراء'], 403);
        }

        $profileRequest = ProfileUpdateRequest::findOrFail($id);

        if ($profileRequest->status !== 'pending') {
            return response()->json(['message' => 'هذا الطلب تم معالجته مسبقاً'], 400);
        }

        $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        // Clean up temporary files stored for this request
        $changes = $profileRequest->requested_changes;
        $fileKeys = [
            'profile_photo_path', 'passport_photo_path', 'identity_proof_path',
            'national_id_photo_path', 'contract_photo_path', 'clearance_certificate_path',
            'non_bankruptcy_certificate_path', 'experience_certificate_path',
            'non_employment_certificate_path', 'tb_health_certificate_path',
            'academic_qualification_path', 'activity_license_path'
        ];
        foreach ($fileKeys as $key) {
            if (isset($changes[$key])) {
                $tempPath = $changes[$key];
                if (Storage::disk('public')->exists($tempPath)) {
                    Storage::disk('public')->delete($tempPath);
                }
            }
        }

        // Update request status
        $profileRequest->status = 'rejected';
        $profileRequest->admin_notes = $request->input('admin_notes');
        $profileRequest->processed_by = auth()->id();
        $profileRequest->processed_at = now();
        $profileRequest->save();

        // Notify the user
        $user = User::find($profileRequest->user_id);
        if ($user) {
            $user->notify(new SystemNotification(
                'تم رفض تعديل بياناتك الشخصية',
                'تم رفض طلبك لتعديل البيانات. السبب: ' . $request->input('admin_notes'),
                'warning',
                '/profile'
            ));
        }

        return response()->json([
            'message' => 'تم رفض الطلب بنجاح وإرسال إشعار للموظف/الوكيل بالسبب',
            'data' => $profileRequest
        ]);
    }

    /**
     * Get the current user's profile update request status.
     */
    public function currentStatus()
    {
        $user = auth()->user();
        $pendingRequest = ProfileUpdateRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            $changes = $pendingRequest->requested_changes;
            $fileFields = [
                'profile_photo_path' => 'profile_photo_url',
                'passport_photo_path' => 'passport_photo_url',
                'identity_proof_path' => 'identity_proof_url',
                'national_id_photo_path' => 'national_id_photo_url',
                'contract_photo_path' => 'contract_photo_url',
                'clearance_certificate_path' => 'clearance_certificate_url',
                'non_bankruptcy_certificate_path' => 'non_bankruptcy_certificate_url',
                'experience_certificate_path' => 'experience_certificate_url',
                'non_employment_certificate_path' => 'non_employment_certificate_url',
                'tb_health_certificate_path' => 'tb_health_certificate_url',
                'academic_qualification_path' => 'academic_qualification_url',
                'activity_license_path' => 'activity_license_url',
            ];
            foreach ($fileFields as $pathKey => $urlKey) {
                if (isset($changes[$pathKey])) {
                    $changes[$urlKey] = '/storage/' . $changes[$pathKey];
                }
            }
            $pendingRequest->requested_changes = $changes;
        }

        return response()->json([
            'has_pending' => $pendingRequest !== null,
            'pending_request' => $pendingRequest
        ]);
    }
}
