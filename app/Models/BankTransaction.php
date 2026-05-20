<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_date',
        'reference_number',
        'bank_name',
        'account_number',
        'amount',
        'type', // deposit, withdrawal
        'reconciled',
        'notes',
        'document_id', // to link with financial archive if needed
        'transaction_type',
        'source_bank',
        'destination_bank',
        'agent_name',
        'branch_agent_id',
        'payment_method',
        'voucher_image',
        'payer_name',
        'payer_phone',
        'source_account_number'
    ];

    protected $casts = [
        'amount' => 'float',
        'reconciled' => 'boolean',
        'transaction_date' => 'date:Y-m-d',
    ];
}
