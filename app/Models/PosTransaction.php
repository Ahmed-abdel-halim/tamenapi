<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    protected $fillable = [
        'pos_machine_id',
        'transaction_date',
        'amount',
        'transactions_count',
        'reference_number',
        'report_file',
        'is_reconciled',
        'notes',
    ];

    protected $casts = [
        'amount' => 'float',
        'is_reconciled' => 'boolean',
        'transaction_date' => 'date',
    ];

    public function machine()
    {
        return $this->belongsTo(PosMachine::class, 'pos_machine_id');
    }
}
