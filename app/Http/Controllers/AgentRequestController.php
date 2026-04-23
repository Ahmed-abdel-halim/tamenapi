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
