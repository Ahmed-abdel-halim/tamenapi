<?php

namespace App\Http\Controllers;

use App\Models\CargoInsuranceDocument;
use App\Models\BranchAgent;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CargoInsuranceDocumentController extends Controller
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
                        $branchAgentId = $user->branch_agent_id ?? null;
                        if (!$branchAgentId) {
                            $branchAgent = BranchAgent::where('user_id', $userId)->first();
                            $branchAgentId = $branchAgent->id ?? null;
                        }
                    }
                }
            }

            $query = CargoInsuranceDocument::with(['branchAgent', 'user']);
            
            if (!$isAdmin) {
                if ($branchAgentId) {
                    $query->where('branch_agent_id', $branchAgentId);
                } else {
                    $query->where('user_id', $userId);
                }
            }

            $statusParam = $request->query('status');
            if ($statusParam === 'all') {
                // Return all documents
            } elseif ($statusParam === 'expired' || $statusParam === 'archived' || $request->boolean('archived')) {
                $query->archived();
            } elseif ($statusParam === 'active') {
                $query->active();
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
            // ملاحظة: CargoInsuranceDocument قد يستخدم created_at بدلاً من issue_date إذا لم يكن موجوداً
            // سأستخدم created_at هنا كمثال إذا لم يتوفر issue_date
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
                    if ($document->branchAgent && !empty($document->branchAgent->agency_name)) {
                        $document->agency_name = $document->branchAgent->agency_name;
                        $document->user_name = null;
                        $document->is_agency = true;
                    } else {
                        $userName = $document->user ? ($document->user->name ?? $document->user->username) : null;
                        $document->agency_name = $userName;
                        $document->user_name = $userName;
                        $document->is_agency = false;
                    }
                } else {
                    $document->agency_name = null;
                    $document->user_name = null;
                    $document->is_agency = false;
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
            'cargo_description' => 'required|string',
            'transport_type' => 'required|string',
            'voyage_from' => 'required|string',
            'voyage_to' => 'required|string',
            'sum_insured' => 'required|numeric',
            'premium_amount' => 'required|numeric',
            'whatsapp_number' => 'required|string',
        ]);

        try {
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            $branchAgentId = null;
            if ($userId) {
                $user = User::find($userId);
                $branchAgentId = $user->branch_agent_id ?? null;
                if (!$branchAgentId) {
                    $branchAgent = BranchAgent::where('user_id', $userId)->first();
                    $branchAgentId = $branchAgent->id ?? null;
                }
            }

            // Generate Policy Number
            $last = CargoInsuranceDocument::latest()->first();
            $nextId = ($last->id ?? 0) + 1;
            $policyNumber = 'ML-CRG-' . str_pad($nextId, 5, '0', STR_PAD_LEFT);

            $document = CargoInsuranceDocument::create(array_merge($validated, [
                'policy_number' => $policyNumber,
                'branch_agent_id' => $branchAgentId,
                'user_id' => $userId,
                'status' => 'active'
            ]));

            return response()->json($document, 201);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        return response()->json(CargoInsuranceDocument::with('branchAgent')->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'insured_name' => 'required|string',
            'cargo_description' => 'required|string',
            'transport_type' => 'required|string',
            'voyage_from' => 'required|string',
            'voyage_to' => 'required|string',
            'sum_insured' => 'required|numeric',
            'premium_amount' => 'required|numeric',
            'whatsapp_number' => 'required|string',
        ]);

        $document = CargoInsuranceDocument::findOrFail($id);
        $document->update($validated);
        return response()->json($document);
    }

    public function destroy($id)
    {
        $document = CargoInsuranceDocument::findOrFail($id);
        $document->delete();
        return response()->json(['status' => 'deleted']);
    }

    /**
     * إلغاء وثيقة - Soft Cancel (مخصص للأدمن والموظفين المصرح لهم فقط - يمنع الوكلاء تماماً)
     */
    public function cancel(Request $request, $id)
    {
        try {
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if (!$userId) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }
            $userId = is_numeric($userId) ? (int) $userId : null;
            $user = $userId ? \App\Models\User::find($userId) : null;
            if (!$user) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }

            // منع الوكلاء من الإلغاء تماماً
            if ($user->branch_agent_id || \App\Models\BranchAgent::where('user_id', $user->id)->exists()) {
                return response()->json(['message' => 'الوكلاء غير مصرح لهم بإلغاء الوثائق'], 403);
            }

            // فقط الأدمن والموظفين المصرح لهم
            $isAdmin = $user->is_admin ?? false;
            $authDocs = is_array($user->authorized_documents)
                ? $user->authorized_documents
                : (is_string($user->authorized_documents) ? json_decode($user->authorized_documents, true) : []);
            if (!is_array($authDocs)) $authDocs = [];

            $hasPermission = $isAdmin || !empty($authDocs) || ($user->department_id !== null);
            if (!$hasPermission) {
                return response()->json(['message' => 'غير مصرح لك بإلغاء الوثائق'], 403);
            }

            $validated = $request->validate(['cancel_reason' => 'required|string|max:1000']);
            
            $modelMap = [
                'InsuranceDocumentController' => \App\Models\InsuranceDocument::class,
                'TravelInsuranceDocumentController' => \App\Models\TravelInsuranceDocument::class,
                'ResidentInsuranceDocumentController' => \App\Models\ResidentInsuranceDocument::class,
                'MarineStructureInsuranceDocumentController' => \App\Models\MarineStructureInsuranceDocument::class,
                'ProfessionalLiabilityInsuranceDocumentController' => \App\Models\ProfessionalLiabilityInsuranceDocument::class,
                'PersonalAccidentInsuranceDocumentController' => \App\Models\PersonalAccidentInsuranceDocument::class,
                'SchoolStudentInsuranceDocumentController' => \App\Models\SchoolStudentInsuranceDocument::class,
                'CashInTransitInsuranceDocumentController' => \App\Models\CashInTransitInsuranceDocument::class,
                'CargoInsuranceDocumentController' => \App\Models\CargoInsuranceDocument::class,
                'InternationalInsuranceDocumentController' => \App\Models\InternationalInsuranceDocument::class,
            ];

            $shortName = (new \ReflectionClass($this))->getShortName();
            $modelClass = $modelMap[$shortName] ?? null;

            if (!$modelClass) {
                return response()->json(['message' => 'تعذر تحديد نوع الوثيقة'], 500);
            }

            $document = $modelClass::findOrFail($id);
            if ($document->is_canceled) {
                return response()->json(['message' => 'هذه الوثيقة ملغية بالفعل'], 422);
            }

            $document->update([
                'is_canceled' => true,
                'canceled_at' => now(),
                'canceled_by' => $userId,
                'cancel_reason' => $validated['cancel_reason'],
            ]);

            return response()->json(['message' => 'تم إلغاء الوثيقة بنجاح']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'الوثيقة غير موجودة'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'سبب الإلغاء مطلوب', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Error in cancel document: " . $e->getMessage());
            return response()->json(['message' => 'حدث خطأ أثناء إلغاء الوثيقة'], 500);
        }
    }
}
