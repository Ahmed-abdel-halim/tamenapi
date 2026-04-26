<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentRequest::with(['branchAgent', 'user']);

        // If not admin, only show requests for their branch agent
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && !($user->is_admin ?? false)) {
                $agent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($agent) {
                    $query->where('branch_agent_id', $agent->id);
                }
            }
        }

        // Filter by branch_agent_id for admins
        if ($request->has('branch_agent_id')) {
            $query->where('branch_agent_id', $request->query('branch_agent_id'));
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_type' => 'required|string|in:modification,cancellation',
            'document_type' => 'nullable|string',
            'document_number' => 'required|string',
            'subject' => 'required|string',
            'description' => 'required|string',
        ]);

        $userId = $request->header('X-User-Id') ?? $request->input('user_id');
        $agentId = null;

        if ($userId) {
            $agent = \App\Models\BranchAgent::where('user_id', $userId)->first();
            if ($agent) {
                $agentId = $agent->id;
            }
        }

        $documentRequest = DocumentRequest::create([
            'branch_agent_id' => $agentId,
            'user_id' => $userId,
            'request_type' => $validated['request_type'],
            'document_type' => $validated['document_type'] ?? null,
            'document_number' => $validated['document_number'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'pending'
        ]);

        return response()->json($documentRequest, 201);
    }

    public function update(Request $request, $id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|string|in:pending,accepted,rejected',
            'admin_message' => 'nullable|string'
        ]);

        $documentRequest->update($validated);

        return response()->json($documentRequest);
    }

    public function destroy($id)
    {
        $documentRequest = DocumentRequest::findOrFail($id);
        $documentRequest->delete();

        return response()->json(['message' => 'تم حذف الطلب بنجاح']);
    }
}
