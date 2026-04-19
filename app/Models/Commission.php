<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'document_type',
        'document_number',
        'total_amount',
        'commission_rate',
        'commission_amount',
        'status', // pending, paid
        'payment_date',
        'notes'
    ];

    public function agent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }
}
