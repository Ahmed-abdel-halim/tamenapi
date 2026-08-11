<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolStudentInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number',
        'branch_agent_id',
        'student_name',
        'school_name',
        'grade',
        'birth_date',
        'start_date',
        'end_date',
        'premium_amount',
        'whatsapp_number',
        'status'
    ,
        'user_id',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('end_date')
              ->orWhereDate('end_date', '>=', \Carbon\Carbon::now()->toDateString());
        });
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('end_date')
                     ->whereDate('end_date', '<', \Carbon\Carbon::now()->toDateString());
    }
}
