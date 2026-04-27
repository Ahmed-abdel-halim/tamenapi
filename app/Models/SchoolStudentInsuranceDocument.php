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
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}
