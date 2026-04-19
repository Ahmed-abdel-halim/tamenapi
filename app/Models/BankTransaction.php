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
        'document_id' // to link with financial archive if needed
    ];
}
