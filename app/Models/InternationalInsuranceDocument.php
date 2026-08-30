<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternationalInsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_number', 'external_policy_number', 'insured_name', 'insured_address', 'phone', 'whatsapp_number',
        'chassis_number', 'plate_number', 'vehicle_type_id', 'external_car_id', 'year',
        'vehicle_nationality', 'external_vehicle_nationality_id', 'visited_country', 'external_country_id',
        'start_date', 'number_of_days', 'end_date',
        'item_type', 'number_of_countries', 'daily_premium',
        'premium', 'tax', 'supervision_fees', 'issue_fees', 'stamp', 'total',
        'issue_date', 'branch_agent_id',
    
        'user_id',
        'is_canceled',
        'canceled_at',
        'canceled_by',
        'cancel_reason',
    ];

    protected $appends = ['vehicle_brand'];

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

    public function getVehicleBrandAttribute()
    {
        if ($this->vehicleType) {
            return $this->vehicleType->brand . ($this->vehicleType->category ? ' / ' . $this->vehicleType->category : '');
        }

        if ($this->external_car_id) {
            $cars = \Illuminate\Support\Facades\Cache::remember('lifo_cars_list', 86400, function() {
                try {
                    $response = \Illuminate\Support\Facades\Http::timeout(10)
                        ->withoutVerifying()
                        ->post('https://prodapi.lifo.ly/api/cars/all', [
                            'user_name' => 'adminmli',
                            'pass_word' => '20232024',
                        ]);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['code']) && $data['code'] === 1 && is_array($data['data'])) {
                            return $data['data'];
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error fetching LIFO cars for model: ' . $e->getMessage());
                }
                return [];
            });

            if (is_array($cars)) {
                foreach ($cars as $car) {
                    if (isset($car['id']) && (int)$car['id'] === (int)$this->external_car_id) {
                        return $car['name'] ?? '--';
                    }
                }
            }
        }

        return '--';
    }

    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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
