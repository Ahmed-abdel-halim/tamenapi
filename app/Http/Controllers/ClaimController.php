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

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->damage_type) {
            $query->where('damage_type', 'like', '%' . $request->damage_type . '%');
        }

        $claims = $query->orderBy('created_at', 'desc')->get();
        return response()->json($claims);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'claim_number'        => 'required|string|unique:claims',
            'reference_number'    => 'nullable|string',
            'admin_number'        => 'nullable|string',
            'claim_date'          => 'required|date',
            'accident_date'       => 'required|date',
            'accident_location'   => 'nullable|string',
            'accident_time'       => 'nullable|string',
            'has_fatalities'      => 'nullable|boolean',
            'damage_type'         => 'required|string',
            'other_damage_type'   => 'nullable|string',

            'claimant_name'       => 'required|string',
            'kinship'             => 'required|string',
            'personal_id'         => 'required|string',
            'nationality'         => 'required|string',
            'phone_number'        => 'required|string',
            'claimant_check_number' => 'nullable|string',

            // Driver
            'driver_name'                 => 'nullable|string',
            'driver_nationality'          => 'nullable|string',
            'driver_id_number'            => 'nullable|string',
            'driver_license_number'       => 'nullable|string',
            'driver_license_issue_date'   => 'nullable|date',
            'driver_license_expiry_date'  => 'nullable|date',

            // Damaged body
            'damaged_body_type'           => 'nullable|string',

            // Vehicle
            'damaged_vehicle_model'       => 'nullable|string',
            'damaged_vehicle_plate'       => 'nullable|string',
            'damaged_vehicle_amount'      => 'nullable|numeric',
            'damaged_vehicle_repair_shop' => 'nullable|string',

            // Person
            'damaged_person_name'         => 'nullable|string',
            'damaged_person_amount'       => 'nullable|numeric',

            // Building
            'damaged_building_description' => 'nullable|string',
            'damaged_building_amount'      => 'nullable|numeric',

            // Victim insurance
            'victim_insurance_company'     => 'nullable|string',
            'victim_insurance_number'      => 'nullable|string',
            'victim_insurance_type'        => 'nullable|string',
            'victim_insurance_issue_date'  => 'nullable|date',
            'victim_insurance_expiry_date' => 'nullable|date',

            // Assessor
            'assessor_name'          => 'nullable|string',
            'assessor_phone'         => 'nullable|string',
            'assessor_date'          => 'nullable|date',
            'assessor_amount_dinar'  => 'nullable|numeric',
            'assessor_amount_dollar' => 'nullable|numeric',

            'document_coverage'   => 'nullable|string',
            'document_type'       => 'nullable|string',
            'document_id'         => 'nullable|integer',
            'branch_agent_id'     => 'nullable|integer',
            'additional_documents'=> 'nullable|json',
            'document_manual_data'=> 'nullable|json',
            'fatalities_count'    => 'nullable|integer',
            'damaged_vehicle_details' => 'nullable|string',
            'damaged_person_details' => 'nullable|string',
            'victim_insurance_coverage' => 'nullable|string',
            'damage_costs'        => 'nullable|json',
            'assessor_percentage' => 'nullable|string',
            'assessor_other_amount' => 'nullable|string',
        ]);

        // Handle file uploads
        $validated = $this->handleFileUploads($request, $validated);

        if (isset($validated['additional_documents'])) {
            $validated['additional_documents'] = json_decode($validated['additional_documents'], true);
        }
        if (isset($validated['document_manual_data'])) {
            $validated['document_manual_data'] = json_decode($validated['document_manual_data'], true);
        }
        if (isset($validated['damage_costs'])) {
            $validated['damage_costs'] = json_decode($validated['damage_costs'], true);
        }

        $claim = Claim::create($validated);
        $this->handleReports($request, $claim);

        return response()->json($claim->load('reports'), 201);
    }

    public function update(Request $request, $id)
    {
        $claim = Claim::findOrFail($id);

        $validated = $request->validate([
            'claim_number'        => 'required|string|unique:claims,claim_number,' . $id,
            'reference_number'    => 'nullable|string',
            'admin_number'        => 'nullable|string',
            'claim_date'          => 'required|date',
            'accident_date'       => 'required|date',
            'accident_location'   => 'nullable|string',
            'accident_time'       => 'nullable|string',
            'has_fatalities'      => 'nullable|boolean',
            'damage_type'         => 'required|string',
            'other_damage_type'   => 'nullable|string',
            'claimant_name'       => 'required|string',
            'kinship'             => 'nullable|string',
            'personal_id'         => 'nullable|string',
            'nationality'         => 'nullable|string',
            'phone_number'        => 'required|string',
            'claimant_check_number' => 'nullable|string',
            'driver_name'                 => 'nullable|string',
            'driver_nationality'          => 'nullable|string',
            'driver_id_number'            => 'nullable|string',
            'driver_license_number'       => 'nullable|string',
            'driver_license_issue_date'   => 'nullable|date',
            'driver_license_expiry_date'  => 'nullable|date',
            'damaged_body_type'           => 'nullable|string',
            'damaged_vehicle_model'       => 'nullable|string',
            'damaged_vehicle_plate'       => 'nullable|string',
            'damaged_vehicle_amount'      => 'nullable|numeric',
            'damaged_vehicle_repair_shop' => 'nullable|string',
            'damaged_person_name'         => 'nullable|string',
            'damaged_person_amount'       => 'nullable|numeric',
            'damaged_building_description' => 'nullable|string',
            'damaged_building_amount'      => 'nullable|numeric',
            'victim_insurance_company'     => 'nullable|string',
            'victim_insurance_number'      => 'nullable|string',
            'victim_insurance_type'        => 'nullable|string',
            'victim_insurance_issue_date'  => 'nullable|date',
            'victim_insurance_expiry_date' => 'nullable|date',
            'assessor_name'          => 'nullable|string',
            'assessor_phone'         => 'nullable|string',
            'assessor_date'          => 'nullable|date',
            'assessor_amount_dollar' => 'nullable|numeric',
            'status'                 => 'nullable|string',
            'additional_documents'   => 'nullable|json',
            'document_manual_data'   => 'nullable|json',
            'fatalities_count'       => 'nullable|integer',
            'damaged_vehicle_details'=> 'nullable|string',
            'damaged_person_details' => 'nullable|string',
            'victim_insurance_coverage' => 'nullable|string',
            'damage_costs'           => 'nullable|json',
            'assessor_percentage'    => 'nullable|string',
            'assessor_other_amount'  => 'nullable|string',
        ]);

        $validated = $this->handleFileUploads($request, $validated, $claim);

        if (isset($validated['additional_documents'])) {
            $validated['additional_documents'] = json_decode($validated['additional_documents'], true);
        }
        if (isset($validated['document_manual_data'])) {
            $validated['document_manual_data'] = json_decode($validated['document_manual_data'], true);
        }
        if (isset($validated['damage_costs'])) {
            $validated['damage_costs'] = json_decode($validated['damage_costs'], true);
        }

        $claim->update($validated);
        $this->handleReports($request, $claim);

        return response()->json($claim->load('reports'));
    }

    private function handleFileUploads(Request $request, array $validated, ?Claim $claim = null): array
    {
        $fileFields = [
            'driver_photo'            => 'claim_driver_photos',
            'driver_license_photo'    => 'claim_driver_photos',
            'victim_insurance_photo'  => 'claim_victim_docs',
            'assessor_report_photo'   => 'claim_assessor_reports',
        ];

        foreach ($fileFields as $field => $folder) {
            if ($request->hasFile($field)) {
                // Delete old file if updating
                if ($claim && $claim->$field) {
                    Storage::disk('public')->delete($claim->$field);
                }
                $validated[$field] = $request->file($field)->store($folder, 'public');
            }
        }

        // Handle multiple photo arrays
        $photoArrayFields = [
            'damaged_vehicle_photos' => 'claim_vehicle_photos',
            'damaged_person_photos'  => 'claim_person_photos',
            'damaged_building_photos' => 'claim_building_photos',
        ];

        foreach ($photoArrayFields as $field => $folder) {
            if ($request->hasFile($field)) {
                $files = $request->file($field);
                $paths = [];
                foreach ((array)$files as $file) {
                    $paths[] = $file->store($folder, 'public');
                }
                $existing = ($claim && $claim->$field) ? $claim->$field : [];
                $validated[$field] = array_merge($existing, $paths);
            }
        }

        $invoiceFields = [
            'vehicle_parts_invoice' => 'vehicle',
            'vehicle_repair_invoice' => 'vehicle',
            'vehicle_other_invoice' => 'vehicle',
            'person_hospital_invoice' => 'person',
            'person_medical_tests_invoice' => 'person',
            'person_other_invoice' => 'person',
            'building_materials_invoice' => 'building',
            'building_labor_invoice' => 'building',
            'building_maintenance_invoice' => 'building',
            'building_other_invoice' => 'building',
        ];

        $invoicesJson = ($claim && $claim->damage_cost_invoices) ? $claim->damage_cost_invoices : [];

        foreach ($invoiceFields as $field => $type) {
            if ($request->hasFile($field)) {
                if ($claim && isset($invoicesJson[$field])) {
                    Storage::disk('public')->delete($invoicesJson[$field]);
                }
                $invoicesJson[$field] = $request->file($field)->store("claim_invoices/{$type}", 'public');
            }
        }
        
        if (!empty($invoicesJson)) {
            $validated['damage_cost_invoices'] = $invoicesJson;
        }

        return $validated;
    }

    public function destroy($id)
    {
        $claim = Claim::findOrFail($id);
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
        
        // If we are updating, we should ideally update existing reports.
        // For simplicity, we'll clear existing ones if new reports are provided.
        // But we must be careful not to lose images if the frontend doesn't send them back.
        // In this system's current state, it seems reports are replaced.
        if ($reportsCount > 0) {
            $claim->reports()->delete();
        }

        for ($i = 0; $i < $reportsCount; $i++) {
            $reportType = $request->input("reports_{$i}_report_type");
            $reportDate = $request->input("reports_{$i}_report_date");
            $preparerName = $request->input("reports_{$i}_preparer_name");
            $reportNumber = $request->input("reports_{$i}_report_number");

            // Sanitize date
            if ($reportDate === 'null' || $reportDate === '') {
                $reportDate = null;
            }

            if ($reportType || $reportDate || $preparerName || $reportNumber || $request->hasFile("reports_{$i}_report_image") || $request->input("reports_{$i}_existing_image")) {
                $imagePath = $request->input("reports_{$i}_existing_image");
                if ($request->hasFile("reports_{$i}_report_image")) {
                    $imagePath = $request->file("reports_{$i}_report_image")->store('claim_reports', 'public');
                }

                $claim->reports()->create([
                    'report_type'       => $reportType,
                    'other_report_type' => $request->input("reports_{$i}_other_report_type"),
                    'report_date'       => $reportDate,
                    'preparer_name'     => $preparerName,
                    'report_number'     => $reportNumber,
                    'report_image'      => $imagePath,
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
            'transfer_type'       => 'required|string',
            'other_transfer_type' => 'nullable|string',
        ]);

        $details = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'detail_')) {
                $details[str_replace('detail_', '', $key)] = $value;
            }
        }
        foreach ($request->allFiles() as $key => $file) {
            if (str_starts_with($key, 'detail_')) {
                $path = $file->store('claim_transfers', 'public');
                $details[str_replace('detail_', '', $key)] = $path;
            }
        }

        $transfer = $claim->transfers()->create([
            'transfer_type'       => $validated['transfer_type'],
            'other_transfer_type' => $validated['other_transfer_type'] ?? null,
            'details'             => $details,
        ]);

        $claim->update(['status' => $validated['transfer_type']]);

        return response()->json($transfer, 201);
    }

    public function searchDocuments(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string',
            'search'        => 'nullable|string',
        ]);

        $modelClass = '\\App\\Models\\' . $request->document_type;
        if (!class_exists($modelClass)) {
            return response()->json([], 200);
        }

        $query = $modelClass::query();

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
            $query->where(function ($q) use ($request, $modelClass) {
                $q->where('insurance_number', 'like', "%{$request->search}%");
                $columns = \Illuminate\Support\Facades\Schema::getColumnListing((new $modelClass)->getTable());
                if (in_array('insured_name', $columns)) {
                    $q->orWhere('insured_name', 'like', "%{$request->search}%");
                } elseif (method_exists($modelClass, 'passengers')) {
                    $q->orWhereHas('passengers', function ($pq) use ($request) {
                        $pq->where('name_ar', 'like', "%{$request->search}%")
                           ->orWhere('name_en', 'like', "%{$request->search}%");
                    });
                }
            });
        }

        $documents = $query->orderBy('created_at', 'desc')->limit(200)->get();
        $formattedDocuments = $documents->map(function ($doc) {
            $name = $doc->insured_name;
            if (!$name && method_exists($doc, 'passengers')) {
                $mainPassenger = $doc->passengers()->where('is_main_passenger', true)->first();
                $name = $mainPassenger ? $mainPassenger->name_ar : null;
            }
            return [
                'id'              => $doc->id,
                'insurance_number' => $doc->insurance_number,
                'insured_name'    => $name ?: 'غير محدد',
            ];
        });

        return response()->json($formattedDocuments);
    }

    public function fetchDocumentInfo(Request $request)
    {
        $request->validate([
            'document_type'    => 'required|string',
            'insurance_number' => 'required|string',
        ]);

        $modelClass = '\\App\\Models\\' . $request->document_type;
        if (!class_exists($modelClass)) {
            return response()->json(['message' => 'نوع الوثيقة غير موجود'], 404);
        }

        $document = $modelClass::where('insurance_number', $request->insurance_number)->first();
        if (!$document) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        }

        if (!$document->insured_name && method_exists($document, 'passengers')) {
            $mainPassenger = $document->passengers()->where('is_main_passenger', true)->first();
            $document->insured_name = $mainPassenger ? $mainPassenger->name_ar : 'غير محدد';
        }

        if ($request->document_type === 'InsuranceDocument') {
            $document->load(['vehicleType', 'plate']);
        }

        if (in_array($request->document_type, ['TravelInsuranceDocument', 'ResidentInsuranceDocument'])) {
            $document->load('passengers');
        }

        return response()->json($document);
    }
}
