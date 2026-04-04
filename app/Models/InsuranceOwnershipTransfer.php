<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceOwnershipTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_document_id',
        'previous_plate_id',
        'previous_plate_number_manual',
        'previous_insured_name',
        'previous_phone',
        'previous_driving_license_number',
        'new_plate_id',
        'new_plate_number_manual',
        'new_insured_name',
        'new_phone',
        'new_driving_license_number',
        'transferred_at',
    ];

    protected $casts = [
        'transferred_at' => 'datetime',
    ];

    public function insuranceDocument()
    {
        return $this->belongsTo(InsuranceDocument::class);
    }

    public function previousPlate()
    {
        return $this->belongsTo(Plate::class, 'previous_plate_id');
    }

    public function newPlate()
    {
        return $this->belongsTo(Plate::class, 'new_plate_id');
    }
}

