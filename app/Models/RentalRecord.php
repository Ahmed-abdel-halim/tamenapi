<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'rental_voucher_id',
        'from_date',
        'to_date',
        'apartments_count',
        'total_amount',
        'recipient_name',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
        'total_amount' => 'float',
    ];

    public function voucher()
    {
        return $this->belongsTo(RentalVoucher::class, 'rental_voucher_id');
    }
}
