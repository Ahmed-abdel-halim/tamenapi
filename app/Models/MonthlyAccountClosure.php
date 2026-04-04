<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyAccountClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'year',
        'month',
        'due_amount',
        'paid_amount',
        'remaining_amount',
        'documents_data',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'due_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'documents_data' => 'array',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}

