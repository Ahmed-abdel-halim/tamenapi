<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\BranchAgent;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    private function syncCommissions()
    {
        $agents = BranchAgent::all()->keyBy('id');
        
        $tables = [
            'insurance_documents' => function($doc) { return $doc->insurance_type ?? 'تأمين سيارات إجباري'; },
            'travel_insurance_documents' => 'تأمين المسافرين',
            'resident_insurance_documents' => 'تأمين الوافدين',
            'marine_structure_insurance_documents' => 'تأمين الهياكل البحرية',
            'professional_liability_insurance_documents' => 'تأمين المسؤولية المهنية (الطبية)',
            'personal_accident_insurance_documents' => 'تأمين الحوادث الشخصية',
            'school_student_insurance_documents' => 'تأمين حماية طلاب المدارس',
            'cash_in_transit_insurance_documents' => 'تأمين نقل النقدية',
            'cargo_insurance_documents' => 'تأمين شحن البضائع'
        ];

        foreach ($tables as $table => $typeResolver) {
            $docs = DB::table($table)->get();
            foreach ($docs as $doc) {
                if (!$doc->branch_agent_id || !isset($agents[$doc->branch_agent_id])) continue;
                
                $agent = $agents[$doc->branch_agent_id];
                $docType = is_callable($typeResolver) ? $typeResolver($doc) : $typeResolver;
                $docNumber = $doc->insurance_number ?? (string)$doc->id;
                
                $percentages = is_string($agent->document_percentages) ? json_decode($agent->document_percentages, true) : ($agent->document_percentages ?? []);
                
                $percentageKey = $docType;
                if (in_array($docType, ['تأمين سيارات إجباري', 'تأمين إجباري سيارات', 'تأمين سيارة جمرك', 'تأمين سيارات أجنبية', 'تأمين طرف ثالث سيارات'])) {
                    $percentageKey = 'تأمين سيارات';
                }

                $percentage = $percentages[$percentageKey] ?? $percentages[$docType] ?? $percentages['تأمين سيارات إجباري'] ?? 0;
                
                $premium = $doc->premium ?? 0;
                $total_amount = $doc->total ?? 0;
                $commission_amount = $premium * ($percentage / 100);

                $commission = Commission::where('document_type', $docType)
                    ->where('document_number', $docNumber)
                    ->first();
                
                if (!$commission) {
                    Commission::create([
                        'branch_agent_id' => $agent->id,
                        'document_type' => $docType,
                        'document_number' => $docNumber,
                        'total_amount' => $total_amount,
                        'commission_rate' => $percentage,
                        'commission_amount' => $commission_amount,
                        'status' => 'pending',
                        'created_at' => $doc->created_at ?? now(),
                        'updated_at' => $doc->updated_at ?? now()
                    ]);
                } else {
                    if ($commission->status === 'pending') {
                        $commission->update([
                            'branch_agent_id' => $agent->id,
                            'total_amount' => $total_amount,
                            'commission_rate' => $percentage,
                            'commission_amount' => $commission_amount,
                        ]);
                    }
                }
            }
        }
    }

    public function index(Request $request)
    {
        try {
            $this->syncCommissions();
        } catch (\Exception $e) {
            // Silently fail the sync if any table doesn't exist yet to prevent blocking the page
        }

        $query = Commission::with('agent');

        if ($request->has('agent_id')) {
            $query->where('branch_agent_id', $request->agent_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_agent_id' => 'required|exists:branches_agents,id',
            'document_type' => 'required|string',
            'document_number' => 'required|string',
            'total_amount' => 'required|numeric',
            'commission_rate' => 'required|numeric',
            'commission_amount' => 'required|numeric',
            'status' => 'required|in:pending,paid',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string'
        ]);

        $commission = Commission::create($validated);
        return response()->json($commission, 201);
    }

    public function update(Request $request, $id)
    {
        $commission = Commission::findOrFail($id);
        $commission->update($request->all());
        return response()->json($commission);
    }

    public function destroy($id)
    {
        Commission::destroy($id);
        return response()->json(['message' => 'Commission deleted']);
    }

    public function markAsPaid($id)
    {
        $commission = Commission::findOrFail($id);
        $commission->update([
            'status' => 'paid',
            'payment_date' => now()
        ]);
        return response()->json($commission);
    }
}
