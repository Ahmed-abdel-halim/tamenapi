<?php

namespace App\Http\Controllers;

use App\Models\AgentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = AgentRequest::with(['branchAgent', 'user']);

        if ($request->has('branch_agent_id')) {
            $query->where('branch_agent_id', $request->branch_agent_id);
        }

        // If not admin, only show requests from their branch agent
        $user = $request->user();
        if (!$user->is_admin) {
            // Find the branch agent associated with this user
            $branchAgent = \App\Models\BranchAgent::where('user_id', $user->id)->first();
            if ($branchAgent) {
                $query->where('branch_agent_id', $branchAgent->id);
            } else {
                return []; // No agent associated
            }
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'type' => 'required|in:stock,support,financial,commission,maintenance,marketing,training,legal,limit_increase,other',
            'priority' => 'required|in:normal,urgent',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attachments' => 'nullable|array',
        ]);

        $agentRequest = AgentRequest::create([
            'branch_agent_id' => $validated['branch_agent_id'],
            'user_id' => Auth::id(),
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'pending',
            'attachments' => $validated['attachments'] ?? [],
        ]);

        // إرسال إشعار للمشرفين
        try {
            $admins = \App\Models\User::where('is_admin', true)->get();
            $agentName = $agentRequest->branchAgent?->agency_name ?? 'الوكيل';
            if ($agentRequest->type === 'stock') {
                $title = 'طلب مخزون جديد';
                $message = "طلب مخزون جديد (تحت الطلب) من الوكيل ({$agentName}): {$agentRequest->subject}";
            } else {
                $title = 'طلب جديد من وكيل';
                $message = "طلب جديد من {$agentName}: {$agentRequest->subject}";
            }
            $url = "/agent-requests";
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\SystemNotification($title, $message, 'info', $url));
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in AgentRequest store: ' . $ne->getMessage());
        }

        return response()->json($agentRequest, 201);
    }

    public function show(AgentRequest $agentRequest)
    {
        $user = Auth::user();
        if (!$user->is_admin) {
            $branchAgent = \App\Models\BranchAgent::where('user_id', $user->id)->first();
            if (!$branchAgent || $agentRequest->branch_agent_id !== $branchAgent->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        return $agentRequest->load(['branchAgent', 'user']);
    }

    public function update(Request $request, AgentRequest $agentRequest)
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Only admins can process requests'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,rejected',
            'admin_notes' => 'nullable|string',
        ]);

        $agentRequest->update($validated);

        // إرسال إشعار للوكيل
        try {
            if ($agentRequest->user_id) {
                $agentUser = \App\Models\User::find($agentRequest->user_id);
                if ($agentUser) {
                    $statusNames = [
                        'pending' => $agentRequest->type === 'stock' ? 'تحت الطلب' : 'قيد الانتظار',
                        'processing' => 'جاري المعالجة',
                        'completed' => $agentRequest->type === 'stock' ? 'نفذت' : 'مكتمل (تم التنفيذ)',
                        'rejected' => 'مرفوض'
                    ];
                    $statusText = $statusNames[$validated['status']] ?? $validated['status'];
                    
                    if ($agentRequest->type === 'stock') {
                        if ($validated['status'] === 'completed') {
                            $title = 'تم تنفيذ طلب المخزون';
                            $message = "تم تنفيذ طلب المخزون الخاص بك بنجاح (نفذت) المعنون بـ ({$agentRequest->subject})";
                        } elseif ($validated['status'] === 'rejected') {
                            $title = 'تم رفض طلب المخزون';
                            $message = "تم رفض طلب المخزون الخاص بك المعنون بـ ({$agentRequest->subject})";
                        } else {
                            $title = 'تحديث حالة طلب المخزون';
                            $message = "تم تحديث طلب المخزون الخاص بك ({$agentRequest->subject}) إلى: {$statusText}";
                        }
                    } else {
                        $title = 'تحديث حالة طلبك';
                        $message = "تمت معالجة طلبك المعنون بـ ({$agentRequest->subject}) وتغيير حالته إلى: {$statusText}";
                    }
                    
                    // Check if it's agent and determine direct link
                    $url = $agentUser->branchAgent ? "/branches-agents/{$agentUser->branchAgent->id}?tab=requests" : "/agent-requests";
                    $agentUser->notify(new \App\Notifications\SystemNotification($title, $message, $validated['status'] === 'rejected' ? 'error' : 'success', $url));
                }
            }
        } catch (\Exception $ne) {
            \Illuminate\Support\Facades\Log::error('Notification error in AgentRequest update: ' . $ne->getMessage());
        }

        return $agentRequest;
    }

    public function destroy(AgentRequest $agentRequest)
    {
        $user = Auth::user();
        if (!$user->is_admin && $agentRequest->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($agentRequest->status !== 'pending' && !$user->is_admin) {
            return response()->json(['message' => 'Cannot delete a processed request'], 403);
        }

        $agentRequest->delete();
        return response()->json(null, 204);
    }
}
