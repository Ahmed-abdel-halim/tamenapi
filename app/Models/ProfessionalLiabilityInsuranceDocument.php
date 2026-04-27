<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessionalLiabilityInsuranceDocument extends Model
{
    use HasFactory;

    protected $table = 'professional_liability_insurance_documents';

    protected $fillable = [
        'insurance_number',
        'issue_date',
        'start_date',
        'end_date',
        'duration',
        'contract_relation',
        'contractor_name',
        'insured_name',
        'birth_date',
        'age',
        'phone',
        'workplace',
        'gender',
        'nationality',
        'profession',
        'marital_status',
        'premium',
        'tax',
        'stamp',
        'issue_fees',
        'supervision_fees',
        'total',
        'whatsapp_number',
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

