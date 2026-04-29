<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgencyCancellation extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'reason',
        'cancellation_date',
        'custody_handover_details',
        'manager_signature',
        'finance_signature',
        'status',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'cancellation_date' => 'date',
        'custody_handover_details' => 'array',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
