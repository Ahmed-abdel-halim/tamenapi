<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_type', 'insurance_number', 'issue_date', 'plate_id', 'port',
        'start_date', 'end_date', 'duration', 'third_party_purpose', 'foreign_car_country', 'foreign_car_purpose',
        'chassis_number', 'plate_number_manual', 'vehicle_type_id', 'color', 'year', 'manufacturing_country', 'fuel_type',
        'license_purpose', 'engine_power', 'authorized_passengers', 'load_capacity',
        'insured_name', 'phone', 'driving_license_number', 'premium', 'tax',
        'stamp', 'issue_fees', 'supervision_fees', 'total', 'print_type', 'branch_agent_id',
    ];

    protected $casts = [
        'issue_date' => 'datetime',
        'start_date' => 'date',
        'end_date' => 'date',
        'premium' => 'decimal:2',
        'tax' => 'decimal:2',
        'stamp' => 'decimal:2',
        'issue_fees' => 'decimal:2',
        'supervision_fees' => 'decimal:2',
        'total' => 'decimal:2',
        'load_capacity' => 'decimal:2',
    ];

    public function plate()
    {
        return $this->belongsTo(Plate::class);
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
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
