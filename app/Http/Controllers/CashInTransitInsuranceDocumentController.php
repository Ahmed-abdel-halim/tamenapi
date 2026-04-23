<?php

namespace App\Http\Controllers;

use App\Models\CashInTransitInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashInTransitInsuranceDocumentController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = $request->header('X-User-Id') ?? $request->query('user_id');
            $isAdmin = false;
            $branchAgentId = null;

            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    $isAdmin = $user->is_admin ?? false;
                    if (!$isAdmin) {
                        $branchAgent = BranchAgent::where('user_id', $userId)->first();
                        $branchAgentId = $branchAgent->id ?? null;
                    }
                }
            }

            $query = CashInTransitInsuranceDocument::with('branchAgent');
            
            if (!$isAdmin && $branchAgentId) {
                $query->where('branch_agent_id', $branchAgentId);
            }

            // إضافة ميزة البحث
            $search = $request->query('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('policy_number', 'like', "%{$search}%")
                      ->orWhere('insured_name', 'like', "%{$search}%");
                });
            }
            // فلتر الوكيل (للادمن)
            if ($isAdmin && $request->has('branch_agent_id')) {
                $query->where('branch_agent_id', $request->query('branch_agent_id'));
            }

            // فلاتر التاريخ (السنة، الشهر، اليوم)
            $dateField = 'created_at'; 
            if ($request->has('year')) {
                $query->whereYear($dateField, $request->query('year'));
            }
            if ($request->has('month')) {
                $query->whereMonth($dateField, $request->query('month'));
            }
            if ($request->has('day')) {
                $query->whereDay($dateField, $request->query('day'));
            }
            $perPage = $request->query('per_page', 10);
            $documents = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $documents->getCollection()->transform(function ($document) use ($isAdmin) {
                if ($isAdmin) {
                    $document->agency_name = $document->branchAgent ? ($document->branchAgent->agency_name ?? null) : null;
                } else {
                    $document->agency_name = null;
                }
                return $document;
            });
            
            return response()->json($documents);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'insured_name' => 'required|string',
            'transit_from' => 'nullable|string',
            'transit_to' => 'nullable|string',
            'limit_per_transit' => 'required|numeric',
            'annual_turnover' => 'nullable|numeric',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'premium_amount' => 'required|numeric',
        ]);

        try {
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            $branchAgentId = null;
            if ($userId) {
                $branchAgent = BranchAgent::where('user_id', $userId)->first();
                $branchAgentId = $branchAgent->id ?? null;
            }

            // Generate Policy Number
            $last = CashInTransitInsuranceDocument::latest()->first();
            $nextId = ($last->id ?? 0) + 1;
            $policyNumber = 'ML-CIT-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $document = CashInTransitInsuranceDocument::create(array_merge($validated, [
                'policy_number' => $policyNumber,
                'branch_agent_id' => $branchAgentId,
                'status' => 'active'
            ]));

            return response()->json($document, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return response()->json(CashInTransitInsuranceDocument::with('branchAgent')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $document = CashInTransitInsuranceDocument::findOrFail($id);
        $document->update($request->all());
        return response()->json($document);
    }

    public function destroy($id)
    {
        $document = CashInTransitInsuranceDocument::findOrFail($id);
        $document->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function print($id)
    {
        $document = CashInTransitInsuranceDocument::findOrFail($id);
        return view('cash-in-transit-insurance-documents.print', compact('document'));
    }
}
