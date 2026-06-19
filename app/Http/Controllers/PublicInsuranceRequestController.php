<?php

namespace App\Http\Controllers;

use App\Models\PublicInsuranceRequest;
use Illuminate\Http\Request;

class PublicInsuranceRequestController extends Controller
{
    /**
     * قائمة جميع الطلبات (للأدمن)
     */
    public function index(Request $request)
    {
        $query = PublicInsuranceRequest::orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('insurance_type', 'like', "%{$search}%");
            });
        }

        return response()->json($query->get());
    }

    /**
     * إرسال طلب تأمين جديد من الزائر (بدون تسجيل دخول)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'whatsapp' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'insurance_type' => 'nullable|string|max:255',
            'request_type' => 'required|in:new,renewal',
            'previous_policy_number' => 'nullable|string|max:100',
            'payment_method' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240', // max 10MB
        ]);

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('insurance_requests', 'public');
            $validated['attachment_url'] = '/storage/' . $path;
        }
        unset($validated['attachment']);

        $validated['status'] = 'pending';

        $insuranceRequest = PublicInsuranceRequest::create($validated);

        // إرسال إشعار للمشرفين
        try {
            $admins = \App\Models\User::where('is_admin', true)->get();
            $title = 'طلب تأمين جديد من الموقع';
            $reqType = $validated['request_type'] === 'new' ? 'وثيقة جديدة' : 'تجديد وثيقة';
            $message = "طلب {$reqType} من: {$validated['name']} - الهاتف: {$validated['phone']}";
            if ($validated['insurance_type'] ?? null) {
                $message .= " - نوع التأمين: {$validated['insurance_type']}";
            }
            $url = '/public-insurance-requests';
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'info', $url));
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Notification error in PublicInsuranceRequest store: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'تم إرسال طلبك بنجاح، سيتم التواصل معك قريباً',
            'request' => $insuranceRequest,
        ], 201);
    }

    /**
     * عرض طلب محدد
     */
    public function show($id)
    {
        return response()->json(PublicInsuranceRequest::findOrFail($id));
    }

    /**
     * تحديث حالة الطلب (للأدمن)
     */
    public function update(Request $request, $id)
    {
        $insuranceRequest = PublicInsuranceRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:pending,in_progress,approved,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $insuranceRequest->update($validated);
        return response()->json($insuranceRequest);
    }

    /**
     * حذف طلب
     */
    public function destroy($id)
    {
        $insuranceRequest = PublicInsuranceRequest::findOrFail($id);
        if ($insuranceRequest->attachment_url) {
            $path = str_replace('/storage/', '', $insuranceRequest->attachment_url);
            if ($path && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
            }
        }
        $insuranceRequest->delete();
        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }
}
