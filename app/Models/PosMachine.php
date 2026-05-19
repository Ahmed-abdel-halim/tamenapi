<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosMachine extends Model
{
    protected $fillable = [
        'machine_name',
        'machine_serial',
        'bank_name',
        'merchant_id',
        'location',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function totalSales()
    {
        return $this->transactions()->sum('amount');
    }
}
