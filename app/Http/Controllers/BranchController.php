<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\User;
use App\Models\InsuranceDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BranchController extends Controller
{
    /**
     * Display a listing of branches with stats.
     */
    public function index(Request $request)
    {
        try {
            // If request is for dropdown/select or switcher
            if ($request->boolean('for_select') || $request->input('type') === 'dropdown') {
                $branches = Branch::select('id', 'code', 'name', 'city', 'status', 'is_main_branch')
                    ->orderBy('is_main_branch', 'desc')
                    ->orderBy('name')
                    ->get();
                return response()->json($branches);
            }

            $query = Branch::query();

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('code', 'like', "%{$s}%")
                      ->orWhere('city', 'like', "%{$s}%")
                      ->orWhere('manager_name', 'like', "%{$s}%")
                      ->orWhere('phone', 'like', "%{$s}%");
                });
            }

            if ($request->filled('city')) {
                $query->where('city', $request->input('city'));
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }

            // Include counts
            $query->withCount(['users', 'insuranceDocuments']);

            $perPage = $request->input('per_page', 15);
            $branches = $query->orderBy('is_main_branch', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->paginate($perPage);

            // Calculate additional financial summaries per branch
            $branchItems = $branches->getCollection()->map(function ($branch) {
                $totalRevenue = InsuranceDocument::where('branch_id', $branch->id)
                    ->where(function ($q) {
                        $q->where('is_canceled', 0)
                          ->orWhere('is_canceled', false)
                          ->orWhereNull('is_canceled');
                    })
                    ->sum('total');

                $monthRevenue = InsuranceDocument::where('branch_id', $branch->id)
                    ->where(function ($q) {
                        $q->where('is_canceled', 0)
                          ->orWhere('is_canceled', false)
                          ->orWhereNull('is_canceled');
                    })
                    ->whereMonth('issue_date', Carbon::now()->month)
                    ->whereYear('issue_date', Carbon::now()->year)
                    ->sum('total');

                $todayDocuments = InsuranceDocument::where('branch_id', $branch->id)
                    ->whereDate('issue_date', Carbon::today())
                    ->count();

                $branch->total_revenue = round($totalRevenue, 2);
                $branch->month_revenue = round($monthRevenue, 2);
                $branch->today_documents_count = $todayDocuments;

                return $branch;
            });

            $branches->setCollection($branchItems);

            return response()->json($branches);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب قائمة الفروع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code',
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:نشط,موقف',
            'is_main_branch' => 'nullable|boolean',
            'commercial_registration' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $nextId = (Branch::max('id') ?? 0) + 1;
            $validated['code'] = 'BR-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
        }

        $validated['status'] = $validated['status'] ?? 'نشط';

        $branch = Branch::create($validated);

        return response()->json([
            'message' => 'تم إنشاء الفرع بنجاح',
            'branch' => $branch
        ], 201);
    }

    /**
     * Display the specified branch with detailed analytics.
     */
    public function show($id)
    {
        $branch = Branch::with(['users:id,name,username,job_title,personal_phone,branch_id,is_admin'])
            ->withCount(['users', 'insuranceDocuments'])
            ->findOrFail($id);

        $now = Carbon::now();

        // Statistics
        $totalDocuments = InsuranceDocument::where('branch_id', $id)->count();
        $activeDocuments = InsuranceDocument::where('branch_id', $id)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->where('end_date', '>=', $now->toDateString())
            ->count();

        $expiredDocuments = InsuranceDocument::where('branch_id', $id)
            ->where('end_date', '<', $now->toDateString())
            ->count();

        $canceledDocuments = InsuranceDocument::where('branch_id', $id)
            ->where('is_canceled', 1)
            ->count();

        $totalRevenue = InsuranceDocument::where('branch_id', $id)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->sum('total');

        $monthRevenue = InsuranceDocument::where('branch_id', $id)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->sum('total');

        $recentDocuments = InsuranceDocument::where('branch_id', $id)
            ->with(['plate.city', 'vehicleType', 'user:id,name'])
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'branch' => $branch,
            'statistics' => [
                'total_documents' => $totalDocuments,
                'active_documents' => $activeDocuments,
                'expired_documents' => $expiredDocuments,
                'canceled_documents' => $canceledDocuments,
                'total_revenue' => round($totalRevenue, 2),
                'month_revenue' => round($monthRevenue, 2),
                'employees_count' => $branch->users_count,
            ],
            'recent_documents' => $recentDocuments,
        ]);
    }

    /**
     * Update the specified branch.
     */
    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:branches,code,' . $id,
            'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'manager_name' => 'nullable|string|max:255',
            'manager_phone' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:نشط,موقف',
            'is_main_branch' => 'nullable|boolean',
            'commercial_registration' => 'nullable|string|max:100',
            'tax_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $branch->update($validated);

        return response()->json([
            'message' => 'تم تحديث بيانات الفرع بنجاح',
            'branch' => $branch
        ]);
    }

    /**
     * Remove or deactivate the specified branch.
     */
    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);

        // Check if there are documents or users attached
        $docsCount = InsuranceDocument::where('branch_id', $id)->count();
        $usersCount = User::where('branch_id', $id)->count();

        if ($docsCount > 0 || $usersCount > 0) {
            // Cannot hard delete, deactivate instead
            $branch->status = 'موقف';
            $branch->save();

            return response()->json([
                'message' => "تم إيقاف الفرع لوجود {$docsCount} وثيقة و {$usersCount} موظف مرتبطين به.",
                'deactivated' => true,
                'branch' => $branch
            ]);
        }

        $branch->delete();

        return response()->json([
            'message' => 'تم حذف الفرع بنجاح'
        ]);
    }

    /**
     * Branch Dashboard Statistics (for scoped branch view)
     */
    public function statistics(Request $request, $id = null)
    {
        $branchId = $id ?: $request->input('branch_id');
        $now = Carbon::now();

        $query = InsuranceDocument::query();
        if ($branchId && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        $totalDocs = (clone $query)->count();
        $activeDocs = (clone $query)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->where('end_date', '>=', $now->toDateString())
            ->count();

        $todayDocs = (clone $query)
            ->whereDate('issue_date', Carbon::today())
            ->count();

        $monthDocs = (clone $query)
            ->whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->count();

        $totalRevenue = (clone $query)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->sum('total');

        $monthRevenue = (clone $query)
            ->where(function ($q) {
                $q->where('is_canceled', 0)
                  ->orWhere('is_canceled', false)
                  ->orWhereNull('is_canceled');
            })
            ->whereMonth('issue_date', $now->month)
            ->whereYear('issue_date', $now->year)
            ->sum('total');

        $employeesCount = $branchId && $branchId !== 'all' 
            ? User::where('branch_id', $branchId)->count()
            : User::count();

        return response()->json([
            'branch_id' => $branchId,
            'total_documents' => $totalDocs,
            'active_documents' => $activeDocs,
            'today_documents' => $todayDocs,
            'month_documents' => $monthDocs,
            'total_revenue' => round($totalRevenue, 2),
            'month_revenue' => round($monthRevenue, 2),
            'employees_count' => $employeesCount,
        ]);
    }
}
