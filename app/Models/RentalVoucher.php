<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RentalVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'phone',
        'national_id',
        'personal_photo',
        'id_photo',
        'national_id_photo',
        'contract_photos',
        'notes',
    ];

    protected $casts = [
        'contract_photos' => 'array',
    ];

    public function records()
    {
        return $this->hasMany(RentalRecord::class)->orderBy('from_date', 'asc');
    }
}
