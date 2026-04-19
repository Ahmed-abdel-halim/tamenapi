<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarineStructureInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_number',
        'issue_date',
        'start_date',
        'end_date',
        'duration',
        'structure_type',
        'license_type',
        'license_purpose',
        'vessel_name',
        'registration_code',
        'registration_date',
        'port',
        'registration_authority_id',
        'plate_number',
        'hull_number',
        'manufacturing_material',
        'length',
        'width',
        'depth',
        'manufacturing_year',
        'manufacturing_country',
        'color',
        'fuel_tank_capacity',
        'passenger_count',
        'load_capacity',
        'insured_name',
        'phone',
        'license_number',
        'premium',
        'tax',
        'stamp',
        'issue_fees',
        'supervision_fees',
        'total',
        'branch_agent_id',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_date' => 'date',
        'premium' => 'decimal:3',
        'tax' => 'decimal:3',
        'stamp' => 'decimal:3',
        'issue_fees' => 'decimal:3',
        'supervision_fees' => 'decimal:3',
        'total' => 'decimal:3',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'depth' => 'decimal:2',
        'fuel_tank_capacity' => 'decimal:2',
        'load_capacity' => 'decimal:2',
        'horsepower' => 'decimal:2',
    ];

    public function registrationAuthority()
    {
        return $this->belongsTo(Plate::class, 'registration_authority_id');
    }

    public function engines()
    {
        return $this->hasMany(MarineStructureEngine::class);
    }

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('end_date')
              ->orWhereDate('end_date', '>=', \Carbon\Carbon::now()->toDateString());
        });
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('end_date')
                     ->whereDate('end_date', '<', \Carbon\Carbon::now()->toDateString());
    }
}
