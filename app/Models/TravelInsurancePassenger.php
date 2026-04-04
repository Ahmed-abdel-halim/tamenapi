<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TravelInsurancePassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'travel_insurance_document_id',
        'is_main_passenger',
        'relationship',
        'name_ar',
        'name_en',
        'phone',
        'passport_number',
        'address',
        'birth_date',
        'age',
        'gender',
        'nationality',
    ];

    protected $casts = [
        'is_main_passenger' => 'boolean',
        'birth_date' => 'date',
    ];

    public function travelInsuranceDocument()
    {
        return $this->belongsTo(TravelInsuranceDocument::class);
    }
}
