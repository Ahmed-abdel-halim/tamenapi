<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'name',
        'recipient',
        'category',
        'sub_category',
        'amount',
        'currency',
        'voucher_number',
        'receipt_image',
        'expense_type',
        'expense_date',
        'status',
        'notes',
        'items',
        'expense_category_id',
        'treasury_id',
        'is_indemnity',
        'indemnity_type',
        'payment_source'
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'amount' => 'float',
        'expense_date' => 'date:Y-m-d',
        'is_indemnity' => 'boolean',
        'items' => 'json',
    ];
}
