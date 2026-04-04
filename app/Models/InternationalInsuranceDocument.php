<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternationalInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number', 'insured_name', 'insured_address', 'phone',
        'chassis_number', 'plate_number', 'vehicle_type_id', 'external_car_id', 'year',
        'vehicle_nationality', 'external_vehicle_nationality_id', 'visited_country', 'external_country_id',
        'start_date', 'number_of_days', 'end_date',
        'item_type', 'number_of_countries', 'daily_premium',
        'premium', 'tax', 'supervision_fees', 'issue_fees', 'stamp', 'total',
        'issue_date', 'branch_agent_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'issue_date' => 'datetime',
        'premium' => 'decimal:3',
        'tax' => 'decimal:3',
        'supervision_fees' => 'decimal:3',
        'issue_fees' => 'decimal:3',
        'stamp' => 'decimal:3',
        'total' => 'decimal:3',
        'daily_premium' => 'decimal:3',
    ];

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }
}
