<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashInTransitInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_number',
        'branch_agent_id',
        'insured_name',
        'transit_from',
        'transit_to',
        'limit_per_transit',
        'annual_turnover',
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
