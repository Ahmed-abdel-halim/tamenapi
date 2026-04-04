<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonalAccidentInsuranceDocument extends Model
{
    use HasFactory;

    protected $table = 'personal_accident_insurance_documents';

    protected $fillable = [
        'insurance_number',
        'issue_date',
        'start_date',
        'end_date',
        'duration',
        'name',
        'birth_date',
        'age',
        'phone',
        'id_proof',
        'address',
        'workplace',
        'gender',
        'nationality',
        'profession',
        'claim_authorized_name',
        'premium',
        'tax',
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
        'birth_date' => 'date',
        'premium' => 'decimal:3',
        'tax' => 'decimal:3',
        'stamp' => 'decimal:3',
        'issue_fees' => 'decimal:3',
        'supervision_fees' => 'decimal:3',
        'total' => 'decimal:3',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}

