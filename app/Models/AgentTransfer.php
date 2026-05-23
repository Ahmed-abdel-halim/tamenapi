<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'amount',
        'payment_method',
        'transfer_date',
        'reference_number',
        'bank_name',
        'source_bank',
        'source_account_number',
        'pos_machine_id',
        'voucher_image',
        'representative_name',
        'exchange_office',
        'status',
        'notes',
        'rejection_reason',
        'created_by',
        'approved_by',
        'approval_date',
        'payment_voucher_id',
        'treasury_transaction_id',
        'bank_transaction_id',
        'pos_transaction_id',
    ];

    protected $casts = [
        'amount' => 'float',
        'transfer_date' => 'date:Y-m-d',
        'approval_date' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }

    public function posMachine()
    {
        return $this->belongsTo(PosMachine::class, 'pos_machine_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function paymentVoucher()
    {
        return $this->belongsTo(PaymentVoucher::class, 'payment_voucher_id');
    }

    public function treasuryTransaction()
    {
        return $this->belongsTo(TreasuryTransaction::class, 'treasury_transaction_id');
    }

    public function bankTransaction()
    {
        return $this->belongsTo(BankTransaction::class, 'bank_transaction_id');
    }

    public function posTransaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
