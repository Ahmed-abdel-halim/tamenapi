<?php

namespace App\Http\Controllers;

use App\Models\EmployeeAttendance;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeAttendanceController extends Controller
{
    /**
     * Resolve the requesting user (via Sanctum or X-User-Id header)
     */
    private function resolveAuthUser(Request $request): ?User
    {
        $user = $request->user();
        if (!$user && $request->header('X-User-Id')) {
            $user = User::find($request->header('X-User-Id'));
        }
        return $user;
    }

    /**
     * Apply branch scope to query based on authentication and active branch
     */
    private function applyBranchScope($query, Request $request)
    {
        $authUser = $this->resolveAuthUser($request);
        $isAdmin = (bool)($authUser?->is_admin ?? false);
        $isBranchAdmin = (bool)($authUser?->is_branch_admin ?? false);

        // 1. User strictly assigned to a branch (Branch Admin or non-superadmin)
        if ($authUser?->branch_id && (!$isAdmin || $isBranchAdmin)) {
            $query->where('branch_id', (int)$authUser->branch_id);
            return;
        }

        // 2. Query parameter branch_id
        if ($request->filled('branch_id')) {
            $b = $request->input('branch_id');
            if ($b === 'all') {
                return;
            } elseif ($b === 'hq' || $b === 'none') {
                $query->whereNull('branch_id');
                return;
            } else {
                $query->where('branch_id', (int)$b);
                return;
            }
        }

        // 3. Header X-Branch-Id
        if ($request->header('X-Branch-Id')) {
            $hb = $request->header('X-Branch-Id');
            if ($hb === 'all') {
                return;
            } elseif ($hb === 'hq' || $hb === 'none') {
                $query->whereNull('branch_id');
                return;
            } else {
                $query->where('branch_id', (int)$hb);
                return;
            }
        }
    }

    /**
     * Heartbeat / Ping from active browser tab to track presence and online duration
     */
    public function ping(Request $request)
    {
        $user = $this->resolveAuthUser($request);
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        try {
            $now = Carbon::now();
            $today = $now->toDateString();

            // Update user last seen
            $user->last_seen_at = $now;
            $user->save();

            // Get or initialize today's attendance record
            $attendance = EmployeeAttendance::firstOrNew([
                'user_id' => $user->id,
                'date' => $today,
            ]);

            if (!$attendance->exists) {
                $attendance->branch_id = $user->branch_id;
                $attendance->first_login_at = $now;
                $attendance->last_activity_at = $now;
                $attendance->total_online_seconds = 0;
                $attendance->sessions_count = 1;
                $attendance->status = 'online';
                $attendance->login_ip = $request->ip();
                $attendance->device_info = substr($request->userAgent() ?? '', 0, 255);
            } else {
                // If previous activity was within 180 seconds, accumulate the elapsed time
                if ($attendance->last_activity_at) {
                    $diff = $now->diffInSeconds($attendance->last_activity_at);
                    if ($diff > 0 && $diff <= 180) {
                        $attendance->total_online_seconds = (int)$attendance->total_online_seconds + $diff;
                    }
                }
                $attendance->branch_id = $attendance->branch_id ?? $user->branch_id;
                $attendance->last_activity_at = $now;
                $attendance->status = 'online';
                if (!$attendance->first_login_at) {
                    $attendance->first_login_at = $now;
                }
            }

            $attendance->save();

            return response()->json([
                'success' => true,
                'is_online' => true,
                'total_online_seconds' => $attendance->total_online_seconds,
                'formatted_duration' => $attendance->formatted_duration,
            ]);
        } catch (\Exception $e) {
            Log::error('Ping presence error: ' . $e->getMessage());
            return response()->json(['message' => 'Error recording ping', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get attendance list and presence status for employees for a given date
     */
    public function index(Request $request)
    {
        try {
            $targetDate = $request->input('date', Carbon::today()->toDateString());
            $now = Carbon::now();
            $onlineThreshold = $now->copy()->subSeconds(120);

            $userQuery = User::query()
                ->where('is_active', true)
                ->with(['branch:id,code,name,city', 'department:id,name']);

            // Apply branch isolation
            $this->applyBranchScope($userQuery, $request);

            // Search filter
            if ($request->filled('search')) {
                $s = trim($request->input('search'));
                $userQuery->where(function ($q) use ($s) {
                    $q->where('name', 'like', "%{$s}%")
                      ->orWhere('username', 'like', "%{$s}%")
                      ->orWhere('national_id_number', 'like', "%{$s}%")
                      ->orWhere('job_title', 'like', "%{$s}%")
                      ->orWhere('email', 'like', "%{$s}%");
                });
            }

            // Department filter
            if ($request->filled('department_id') && $request->department_id !== 'all') {
                $userQuery->where('department_id', (int)$request->department_id);
            }

            // Status filter (online / offline)
            if ($request->filled('status')) {
                $status = $request->input('status');
                if ($status === 'online') {
                    $userQuery->whereNotNull('last_seen_at')
                              ->where('last_seen_at', '>=', $onlineThreshold);
                } elseif ($status === 'offline') {
                    $userQuery->where(function ($q) use ($onlineThreshold) {
                        $q->whereNull('last_seen_at')
                          ->orWhere('last_seen_at', '<', $onlineThreshold);
                    });
                }
            }

            // Order by online first, then by name
            $userQuery->orderByRaw("CASE WHEN last_seen_at >= '{$onlineThreshold->toDateTimeString()}' THEN 1 ELSE 2 END ASC")
                      ->orderBy('name', 'asc');

            $perPage = (int)$request->input('per_page', 50);
            $users = $userQuery->paginate($perPage);

            // Fetch attendance records for these users for the target date
            $userIds = $users->pluck('id');
            $attendances = EmployeeAttendance::whereIn('user_id', $userIds)
                ->where('date', $targetDate)
                ->get()
                ->keyBy('user_id');

            // Format items
            $items = $users->map(function ($emp) use ($attendances, $onlineThreshold, $targetDate) {
                $att = $attendances->get($emp->id);
                $isOnline = $emp->last_seen_at && Carbon::parse($emp->last_seen_at)->gte($onlineThreshold);

                // If looking at today and user is online right now, ensure duration is freshly calculated
                $onlineSeconds = $att ? (int)$att->total_online_seconds : 0;
                $formattedDuration = $att ? $att->formatted_duration : '0 دقيقة';

                return [
                    'id' => $emp->id,
                    'name' => $emp->name,
                    'full_name_quad' => $emp->full_name_quad,
                    'job_title' => $emp->job_title ?? 'موظف',
                    'national_id_number' => $emp->national_id_number,
                    'email' => $emp->email,
                    'profile_photo_url' => $emp->profile_photo_url,
                    'branch_id' => $emp->branch_id,
                    'branch' => $emp->branch ? [
                        'id' => $emp->branch->id,
                        'name' => $emp->branch->name,
                        'code' => $emp->branch->code,
                        'city' => $emp->branch->city,
                    ] : null,
                    'department' => $emp->department ? [
                        'id' => $emp->department->id,
                        'name' => $emp->department->name,
                    ] : null,
                    'is_online' => $isOnline,
                    'last_seen_at' => $emp->last_seen_at ? Carbon::parse($emp->last_seen_at)->format('Y-m-d H:i:s') : null,
                    'last_seen_diff' => $emp->last_seen_at ? Carbon::parse($emp->last_seen_at)->diffForHumans() : 'لم يسجل دخول',
                    'attendance' => $att ? [
                        'id' => $att->id,
                        'date' => $att->date?->format('Y-m-d') ?? $targetDate,
                        'first_login_at' => $att->first_login_at ? Carbon::parse($att->first_login_at)->format('Y-m-d H:i:s') : null,
                        'first_login_time' => $att->first_login_time,
                        'last_activity_at' => $att->last_activity_at ? Carbon::parse($att->last_activity_at)->format('Y-m-d H:i:s') : null,
                        'last_activity_time' => $att->last_activity_time,
                        'total_online_seconds' => $onlineSeconds,
                        'formatted_duration' => $formattedDuration,
                        'duration_hours' => round($onlineSeconds / 3600, 2),
                        'sessions_count' => $att->sessions_count ?? 1,
                        'login_ip' => $att->login_ip,
                        'device_info' => $att->device_info,
                    ] : null,
                ];
            });

            return response()->json([
                'success' => true,
                'target_date' => $targetDate,
                'data' => $items,
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
                'per_page' => $users->perPage(),
            ]);
        } catch (\Exception $e) {
            Log::error('Employee attendance index error: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching attendance', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get KPI summary statistics for the branch/system
     */
    public function stats(Request $request)
    {
        try {
            $targetDate = $request->input('date', Carbon::today()->toDateString());
            $now = Carbon::now();
            $onlineThreshold = $now->copy()->subSeconds(120);

            $userQuery = User::query()->where('is_active', true);
            $this->applyBranchScope($userQuery, $request);

            $totalEmployees = (clone $userQuery)->count();

            $onlineNowCount = (clone $userQuery)
                ->whereNotNull('last_seen_at')
                ->where('last_seen_at', '>=', $onlineThreshold)
                ->count();

            $offlineCount = max(0, $totalEmployees - $onlineNowCount);

            // Fetch total duration of attendances in scope
            $scopedUserIds = $userQuery->pluck('id');
            $attendancesQuery = EmployeeAttendance::whereIn('user_id', $scopedUserIds)
                ->where('date', $targetDate);

            $totalSeconds = (int)$attendancesQuery->sum('total_online_seconds');
            $attendedEmployeesCount = (clone $attendancesQuery)->count();

            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $formattedTotalDuration = $hours > 0 ? "{$hours} س و {$minutes} د" : "{$minutes} دقيقة";

            $avgHours = $attendedEmployeesCount > 0 ? round(($totalSeconds / 3600) / $attendedEmployeesCount, 1) : 0;

            return response()->json([
                'success' => true,
                'target_date' => $targetDate,
                'total_employees' => $totalEmployees,
                'online_now_count' => $onlineNowCount,
                'offline_count' => $offlineCount,
                'attended_count' => $attendedEmployeesCount,
                'total_online_seconds' => $totalSeconds,
                'total_hours' => round($totalSeconds / 3600, 2),
                'formatted_total_duration' => $formattedTotalDuration,
                'average_hours' => $avgHours,
            ]);
        } catch (\Exception $e) {
            Log::error('Employee attendance stats error: ' . $e->getMessage());
            return response()->json(['message' => 'Error calculating stats', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get 30-day attendance history for a specific employee
     */
    public function userHistory(Request $request, $userId)
    {
        try {
            $user = User::findOrFail($userId);

            $history = EmployeeAttendance::where('user_id', $user->id)
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get()
                ->map(function ($att) {
                    return [
                        'id' => $att->id,
                        'date' => $att->date?->format('Y-m-d'),
                        'first_login_at' => $att->first_login_at ? Carbon::parse($att->first_login_at)->format('Y-m-d H:i:s') : null,
                        'first_login_time' => $att->first_login_time,
                        'last_activity_at' => $att->last_activity_at ? Carbon::parse($att->last_activity_at)->format('Y-m-d H:i:s') : null,
                        'last_activity_time' => $att->last_activity_time,
                        'total_online_seconds' => $att->total_online_seconds,
                        'formatted_duration' => $att->formatted_duration,
                        'duration_hours' => $att->duration_hours,
                        'sessions_count' => $att->sessions_count,
                        'login_ip' => $att->login_ip,
                        'device_info' => $att->device_info,
                    ];
                });

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'job_title' => $user->job_title,
                    'profile_photo_url' => $user->profile_photo_url,
                ],
                'history' => $history,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error fetching user history', 'error' => $e->getMessage()], 500);
        }
    }
}
