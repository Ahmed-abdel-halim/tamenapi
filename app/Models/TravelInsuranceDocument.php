<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_type',
        'insurance_number',
        'issue_date',
        'start_date',
        'end_date',
        'duration',
        'geographic_area',
        'residence_type',
        'residence_duration',
        'premium',
        'family_members_premium',
        'stamp',
        'issue_fees',
        'supervision_fees',
        'total',
        'branch_agent_id',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'residence_duration' => 'integer',
        'premium' => 'decimal:3',
        'family_members_premium' => 'decimal:3',
        'stamp' => 'decimal:3',
        'issue_fees' => 'decimal:3',
        'supervision_fees' => 'decimal:3',
        'total' => 'decimal:3',
    ];

    public function passengers()
    {
        return $this->hasMany(TravelInsurancePassenger::class);
    }

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}
