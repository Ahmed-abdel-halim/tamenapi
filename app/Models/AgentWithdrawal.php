<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentWithdrawal extends Model
{
    use HasFactory;

    protected $table = 'agent_withdrawals';

    protected $fillable = [
        'branch_agent_id',
        'amount',
        'status',
        'payment_method',
        'admin_notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }
}
