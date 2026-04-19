<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'base_salary',
        'housing_allowance',
        'transportation_allowance',
        'communication_allowance',
        'allowance_amount',
        'bonus_amount',
        'deduction_amount',
        'advance_amount',
        'penalty_amount',
        'other_additions',
        'net_salary',
        'status',
        'delivery_method',
        'custom_delivery_method',
        'extra_fields',
        'paid_at',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'base_salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transportation_allowance' => 'decimal:2',
        'communication_allowance' => 'decimal:2',
        'allowance_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
        'advance_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'other_additions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'extra_fields' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
