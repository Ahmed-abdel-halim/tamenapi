<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentWalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'agent_wallet_transactions';

    protected $fillable = [
        'branch_agent_id',
        'transaction_type',
        'amount',
        'action',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }
}
