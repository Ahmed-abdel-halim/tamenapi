<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number',
        'branch_agent_id',
        'insured_name',
        'cargo_description',
        'transport_type',
        'voyage_from',
        'voyage_to',
        'sum_insured',
        'premium_amount',
        'status'
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}
