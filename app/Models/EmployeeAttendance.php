<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $table = 'employee_attendances';

    protected $fillable = [
        'user_id',
        'branch_id',
        'date',
        'first_login_at',
        'last_activity_at',
        'total_online_seconds',
        'sessions_count',
        'status',
        'login_ip',
        'device_info',
        'notes',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'first_login_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'total_online_seconds' => 'integer',
        'sessions_count' => 'integer',
    ];

    protected $appends = [
        'formatted_duration',
        'duration_hours',
        'first_login_time',
        'last_activity_time',
        'is_online_now',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    /**
     * Checks if the user is currently online (heartbeat received within 2 minutes)
     */
    public function getIsOnlineNowAttribute(): bool
    {
        $lastSeen = $this->last_activity_at ?? $this->user?->last_seen_at;
        if (!$lastSeen) {
            return false;
        }
        return Carbon::parse($lastSeen)->gt(Carbon::now()->subSeconds(120));
    }

    /**
     * Human-friendly Arabic formatted duration (e.g. 5 س و 30 د)
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = (int)$this->total_online_seconds;
        if ($seconds <= 0) {
            return '0 دقيقة';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0 && $minutes > 0) {
            return "{$hours} س و {$minutes} د";
        } elseif ($hours > 0) {
            return "{$hours} ساعة";
        } else {
            return "{$minutes} دقيقة";
        }
    }

    public function getDurationHoursAttribute(): float
    {
        return round($this->total_online_seconds / 3600, 2);
    }

    public function getFirstLoginTimeAttribute(): ?string
    {
        if (!$this->first_login_at) return null;
        return Carbon::parse($this->first_login_at)->format('h:i A');
    }

    public function getLastActivityTimeAttribute(): ?string
    {
        if (!$this->last_activity_at) return null;
        return Carbon::parse($this->last_activity_at)->format('h:i A');
    }
}
