<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TreasuryTransaction extends Model
{
    protected $fillable = [
        'transaction_date',
        'type',
        'amount',
        'description',
        'supplier_phone',
        'source',
        'reference_number',
        'voucher_image',
        'branch_agent_id',
        'expense_destination',
        'payment_source',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'transaction_date' => 'date',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}
