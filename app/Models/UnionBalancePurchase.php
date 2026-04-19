<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnionBalancePurchase extends Model
{
    use HasFactory;

    protected $table = 'union_balance_purchases';

    protected $fillable = [
        'request_number',
        'amount_paid',
        'card_price',
        'union_fee_per_card',
        'company_deposit_per_card',
        'cards_count',
        'total_union_fee',
        'total_company_deposit',
        'payment_method',
        'purchase_date',
        'receipt_image',
        'notes',
    ];

    protected $casts = [
        'amount_paid' => 'float',
        'card_price' => 'float',
        'union_fee_per_card' => 'float',
        'company_deposit_per_card' => 'float',
        'cards_count' => 'integer',
        'total_union_fee' => 'float',
        'total_company_deposit' => 'float',
        'purchase_date' => 'date',
    ];
}
