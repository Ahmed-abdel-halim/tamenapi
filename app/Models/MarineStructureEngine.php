<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarineStructureEngine extends Model
{
    use HasFactory;

    protected $fillable = [
        'marine_structure_insurance_document_id',
        'engine_type',
        'engine_model',
        'fuel_type',
        'engine_number',
        'manufacturing_country',
        'horsepower',
        'installation_date',
        'cylinders_count',
        'installation_type',
    ];

    protected $casts = [
        'installation_date' => 'date',
        'horsepower' => 'decimal:2',
    ];

    public function document()
    {
        return $this->belongsTo(MarineStructureInsuranceDocument::class);
    }
}
