<?php

namespace App\Http\Controllers;

use App\Models\Claim;
use App\Models\ClaimReport;
use App\Models\ClaimTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    public function index(Request $request)
    {
        $query = Claim::with(['document', 'transfers', 'reports']);

        // Filter by branch agent if not admin
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && !$user->is_admin) {
                $branchAgent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($branchAgent) {
                    $query->where('branch_agent_id', $branchAgent->id);
                }
            }
        }

        // Additional Filters
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->damage_type) {
            $query->where('damage_type', $request->damage_type);
        }

        $claims = $query->orderBy('created_at', 'desc')->get();
        return response()->json($claims);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_number' => 'required|string|unique:claims',
            'reference_number' => 'nullable|string',
            'claim_date' => 'required|date',
            'accident_date' => 'required|date',
            'damage_type' => 'required|string',
            'other_damage_type' => 'nullable|string',
            
            'claimant_name' => 'required|string',
            'kinship' => 'required|string',
            'personal_id' => 'required|string',
            'nationality' => 'required|string',
            'phone_number' => 'required|string',
            
            'document_coverage' => 'nullable|string',
            'document_type' => 'nullable|string',
            'document_id' => 'nullable|integer',
            'branch_agent_id' => 'nullable|integer',
        ]);

        $claim = Claim::create($validated);

        // Handle reports
        $this->handleReports($request, $claim);

        return response()->json($claim->load('reports'), 201);
    }

    public function update(Request $request, $id)
    {
        $claim = Claim::findOrFail($id);
        
        $validated = $request->validate([
            'claim_number' => 'required|string|unique:claims,claim_number,' . $id,
            'reference_number' => 'nullable|string',
            'claim_date' => 'required|date',
            'accident_date' => 'required|date',
            'damage_type' => 'required|string',
            'other_damage_type' => 'nullable|string',
            'claimant_name' => 'required|string',
            'kinship' => 'required|string',
            'personal_id' => 'required|string',
            'nationality' => 'required|string',
            'phone_number' => 'required|string',
            'status' => 'nullable|string',
        ]);

        $claim->update($validated);
        
        // Handle reports if any new ones sent
        $this->handleReports($request, $claim);

        return response()->json($claim->load('reports'));
    }

    public function destroy($id)
    {
        $claim = Claim::findOrFail($id);
        // Delete reports files from storage
        foreach ($claim->reports as $report) {
            if ($report->report_image) {
                Storage::disk('public')->delete($report->report_image);
            }
        }
        $claim->delete();
        return response()->json(['message' => 'تم حذف المطالبة بنجاح']);
    }

    private function handleReports(Request $request, Claim $claim)
    {
        $reportsCount = $request->input('reports_count', 0);
        for ($i = 0; $i < $reportsCount; $i++) {
            if ($request->has("reports_{$i}_report_type")) {
                $imagePath = null;
                if ($request->hasFile("reports_{$i}_report_image")) {
                    $imagePath = $request->file("reports_{$i}_report_image")->store('claim_reports', 'public');
                }
                
                $claim->reports()->create([
                    'report_type' => $request->input("reports_{$i}_report_type"),
                    'other_report_type' => $request->input("reports_{$i}_other_report_type"),
                    'report_date' => $request->input("reports_{$i}_report_date"),
                    'preparer_name' => $request->input("reports_{$i}_preparer_name"),
                    'report_number' => $request->input("reports_{$i}_report_number"),
                    'report_image' => $imagePath,
                ]);
            }
        }
    }

    public function show($id)
    {
        $claim = Claim::with(['document', 'transfers', 'reports'])->findOrFail($id);
        return response()->json($claim);
    }

    public function addTransfer(Request $request, $id)
    {
        $claim = Claim::findOrFail($id);

        $validated = $request->validate([
            'transfer_type' => 'required|string',
            'other_transfer_type' => 'nullable|string',
        ]);

        // Process dynamic details. We expect details to be sent as JSON string if it's multipart form data, or flat fields.
        // We will just read anything starting with 'detail_'
        $details = [];
        
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'detail_')) {
                $detailKey = str_replace('detail_', '', $key);
                $details[$detailKey] = $value;
            }
        }

        foreach ($request->allFiles() as $key => $file) {
            if (str_starts_with($key, 'detail_')) {
                $detailKey = str_replace('detail_', '', $key);
                $path = $file->store('claim_transfers', 'public');
                $details[$detailKey] = $path;
            }
        }

        $transfer = $claim->transfers()->create([
            'transfer_type' => $validated['transfer_type'],
            'other_transfer_type' => $validated['other_transfer_type'],
            'details' => $details,
        ]);

        $claim->update(['status' => $validated['transfer_type']]);

        return response()->json($transfer, 201);
    }

    public function searchDocuments(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'search' => 'nullable|string',
        ]);

        $modelClass = '\\App\\Models\\' . $request->document_type;
        
        if (!class_exists($modelClass)) {
            return response()->json([], 200);
        }

        $query = $modelClass::query();

        // Filter by branch agent if not admin
        $userId = $request->header('X-User-Id') ?? $request->query('user_id');
        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user && !$user->is_admin) {
                $branchAgent = \App\Models\BranchAgent::where('user_id', $userId)->first();
                if ($branchAgent) {
                    $query->where('branch_agent_id', $branchAgent->id);
                }
            }
        }
        
        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('insurance_number', 'like', "%{$request->search}%")
                  ->orWhere('insured_name', 'like', "%{$request->search}%");
            });
        }

        $documents = $query->orderBy('created_at', 'desc')
                           ->limit(200)
                           ->get(['id', 'insurance_number', 'insured_name']);

        return response()->json($documents);
    }

    public function fetchDocumentInfo(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'insurance_number' => 'required|string',
        ]);

        // The document type sent from frontend is the class name, e.g. InsuranceDocument
        $modelClass = '\\App\\Models\\' . $request->document_type;
        
        if (!class_exists($modelClass)) {
            return response()->json(['message' => 'نوع الوثيقة غير موجود'], 404);
        }

        $document = $modelClass::where('insurance_number', $request->insurance_number)->first();

        if (!$document) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        }

        // Return document with some extra related info if needed (like vehicle details for car insurance)
        if ($request->document_type === 'InsuranceDocument') {
            $document->load(['vehicleType', 'plate']);
        }

        return response()->json($document);
    }
}
