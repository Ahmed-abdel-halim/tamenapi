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
        'amount',
        'expense_date',
        'status',
        'notes',
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
    ];
}
