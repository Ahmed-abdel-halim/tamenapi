<?php

namespace App\Http\Controllers;

use App\Models\AgencyCancellation;
use App\Models\BranchAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class AgencyCancellationController extends Controller
{
    public function index(Request $request)
    {
        $query = AgencyCancellation::with(['branchAgent', 'creator']);

        if ($request->has('branch_agent_id')) {
            $query->where('branch_agent_id', $request->branch_agent_id);
        }

        $user = Auth::user();
        if (!$user->is_admin) {
            $branchAgent = BranchAgent::where('user_id', $user->id)->first();
            if ($branchAgent) {
                $query->where('branch_agent_id', $branchAgent->id);
            } else {
                return response()->json([]);
            }
        }

        return $query->latest()->get();
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_agent_id' => 'required|exists:branches_agents,id',
                'reason' => 'required|string',
                'cancellation_date' => 'required|date',
                'custody_handover_details' => 'nullable|string',
                'manager_signature' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
                'finance_signature' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }

        $managerSignature = null;
        if ($request->hasFile('manager_signature')) {
            $managerSignature = $request->file('manager_signature')->store('agency_cancellations/signatures', 'public');
        }

        $financeSignature = null;
        if ($request->hasFile('finance_signature')) {
            $financeSignature = $request->file('finance_signature')->store('agency_cancellations/signatures', 'public');
        }

        $cancellation = AgencyCancellation::create([
            'branch_agent_id' => $validated['branch_agent_id'],
            'reason' => $validated['reason'],
            'cancellation_date' => $validated['cancellation_date'],
            'custody_handover_details' => $validated['custody_handover_details'],
            'manager_signature' => $managerSignature,
            'finance_signature' => $financeSignature,
            'status' => 'pending',
            'user_id' => Auth::id(),
        ]);

        return response()->json($cancellation->load(['branchAgent', 'creator']), 201);
    }

    public function show($id)
    {
        try {
            $cancellation = AgencyCancellation::with(['branchAgent', 'creator'])->findOrFail($id);
            
            $user = Auth::user();
            if (!$user->is_admin) {
                $branchAgent = BranchAgent::where('user_id', $user->id)->first();
                if (!$branchAgent || $cancellation->branch_agent_id !== $branchAgent->id) {
                    return response()->json(['message' => 'Unauthorized'], 403);
                }
            }

            return $cancellation;
        } catch (\Exception $e) {
            return response()->json(['message' => 'Record not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $cancellation = AgencyCancellation::findOrFail($id);
            
            $user = Auth::user();
            // Admins can always update. Agents can only update if it's their request and still pending.
            if (!$user->is_admin && ($cancellation->user_id !== $user->id || $cancellation->status !== 'pending')) {
                return response()->json(['message' => 'Unauthorized or request locked'], 403);
            }

            $validated = $request->validate([
                'branch_agent_id' => 'sometimes|exists:branches_agents,id',
                'reason' => 'sometimes|string',
                'cancellation_date' => 'sometimes|date',
                'custody_handover_details' => 'nullable|string',
                'status' => 'sometimes|in:pending,approved,rejected',
                'notes' => 'nullable|string',
            ]);

            if ($request->hasFile('manager_signature')) {
                if ($cancellation->manager_signature) {
                    Storage::disk('public')->delete($cancellation->manager_signature);
                }
                $validated['manager_signature'] = $request->file('manager_signature')->store('agency_cancellations/signatures', 'public');
            }

            if ($request->hasFile('finance_signature')) {
                if ($cancellation->finance_signature) {
                    Storage::disk('public')->delete($cancellation->finance_signature);
                }
                $validated['finance_signature'] = $request->file('finance_signature')->store('agency_cancellations/signatures', 'public');
            }

            $cancellation->update($validated);

            return response()->json($cancellation->load(['branchAgent', 'creator']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating record', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $cancellation = AgencyCancellation::findOrFail($id);
            
            $user = Auth::user();
            if (!$user->is_admin && $cancellation->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            if ($cancellation->status !== 'pending' && !$user->is_admin) {
                return response()->json(['message' => 'Cannot delete processed request'], 403);
            }

            if ($cancellation->manager_signature) {
                Storage::disk('public')->delete($cancellation->manager_signature);
            }
            if ($cancellation->finance_signature) {
                Storage::disk('public')->delete($cancellation->finance_signature);
            }

            $cancellation->delete();
            return response()->json(['message' => 'Deleted successfully'], 204);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error deleting record'], 500);
        }
    }
}
