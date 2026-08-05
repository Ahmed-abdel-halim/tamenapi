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
    ,
        'user_id',
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
