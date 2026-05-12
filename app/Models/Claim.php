<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function document()
    {
        return $this->morphTo();
    }

    public function reports()
    {
        return $this->hasMany(ClaimReport::class);
    }

    protected $casts = [
        'damaged_vehicle_photos' => 'array',
        'damaged_person_photos'  => 'array',
        'damaged_building_photos' => 'array',
        'has_fatalities' => 'boolean',
        'additional_documents' => 'array',
        'document_manual_data' => 'array',
        'damage_costs' => 'array',
        'damage_cost_invoices' => 'array',
    ];

    public function transfers()
    {
        return $this->hasMany(ClaimTransfer::class);
    }
}
