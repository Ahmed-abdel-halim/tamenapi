<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentInsurancePassenger extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_insurance_document_id',
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
        'occupation',
    ];

    protected $casts = [
        'is_main_passenger' => 'boolean',
        'birth_date' => 'date',
        'age' => 'integer',
    ];

    public function residentInsuranceDocument()
    {
        return $this->belongsTo(ResidentInsuranceDocument::class);
    }
}
