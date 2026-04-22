<?php

namespace App\Http\Controllers;

use App\Models\SchoolStudentInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SchoolStudentInsuranceDocumentController extends Controller
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

            $query = SchoolStudentInsuranceDocument::with('branchAgent');
            
            if (!$isAdmin && $branchAgentId) {
                $query->where('branch_agent_id', $branchAgentId);
            }

            // إضافة ميزة البحث
            $search = $request->query('search');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('policy_number', 'like', "%{$search}%")
                      ->orWhere('student_name', 'like', "%{$search}%")
                      ->orWhere('school_name', 'like', "%{$search}%");
                });
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
            'student_name' => 'required|string',
            'school_name' => 'required|string',
            'grade' => 'nullable|string',
            'birth_date' => 'nullable|date',
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
            $last = SchoolStudentInsuranceDocument::latest()->first();
            $nextId = ($last->id ?? 0) + 1;
            $policyNumber = 'ML-SCH-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $document = SchoolStudentInsuranceDocument::create(array_merge($validated, [
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
        return response()->json(SchoolStudentInsuranceDocument::with('branchAgent')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $document = SchoolStudentInsuranceDocument::findOrFail($id);
        $document->update($request->all());
        return response()->json($document);
    }

    public function destroy($id)
    {
        $document = SchoolStudentInsuranceDocument::findOrFail($id);
        $document->delete();
        return response()->json(['status' => 'deleted']);
    }

    public function print($id)
    {
        $document = SchoolStudentInsuranceDocument::findOrFail($id);
        return view('school-student-insurance-documents.print', compact('document'));
    }
}
