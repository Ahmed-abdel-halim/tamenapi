<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_number',
        'branch_agent_id',
        'amount',
        'payment_method',
        'bank_name',
        'reference_number',
        'extra_details',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'extra_details' => 'array'
    ];

    public function agent()
    {
        return $this->belongsTo(BranchAgent::class, 'branch_agent_id');
    }
}
