<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceSupervisionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_type_key',
        'insurance_type_name',
        'fee_rate_percentage',
        'fixed_fee_amount',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'fee_rate_percentage' => 'decimal:2',
        'fixed_fee_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
