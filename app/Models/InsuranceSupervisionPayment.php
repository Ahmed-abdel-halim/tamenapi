<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceSupervisionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'voucher_number',
        'payment_date',
        'amount',
        'payment_method',
        'bank_name',
        'check_number',
        'period_from',
        'period_to',
        'receipt_image',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }
}

