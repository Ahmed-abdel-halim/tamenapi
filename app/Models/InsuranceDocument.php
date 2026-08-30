<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_type',
        'insurance_number',
        'issue_date',
        'plate_id',
        'port',
        'start_date',
        'end_date',
        'duration',
        'third_party_purpose',
        'foreign_car_country',
        'foreign_car_purpose',
        'chassis_number',
        'plate_number_manual',
        'vehicle_type_id',
        'color',
        'year',
        'manufacturing_country',
        'fuel_type',
        'license_purpose',
        'engine_power',
        'authorized_passengers',
        'load_capacity',
        'insured_name',
        'phone',
        'whatsapp_number',
        'driving_license_number',
        'nid_passport',
        'nationality',
        'premium',
        'tax',
        'stamp',
        'issue_fees',
        'supervision_fees',
        'total',
        'print_type',
        'branch_agent_id',
        'email',
        'address',
        'engine_number',
        'engine_cc',
        'vehicle_weight',
        'notes',
        // EIDC Integration Fields - حقول التكامل مع هيئة الإشراف
        'eidc_vehicle_type_id',
        'eidc_vehicle_spec_id',
        'eidc_vehicle_detail_id',
        'eidc_policy_id',
        'eidc_transaction_code',
        'eidc_pdf_url',
        'eidc_sync_status',
        'eidc_error',
        'eidc_synced_at',
    
        'user_id',
        'is_canceled',
        'canceled_at',
        'canceled_by',
        'cancel_reason',
    ];

    protected $casts = [
        // 'issue_date'      => 'date',
        // 'start_date'      => 'date',
        // 'end_date'        => 'date',
        'premium' => 'decimal:2',
        'tax' => 'decimal:2',
        'stamp' => 'decimal:2',
        'issue_fees' => 'decimal:2',
        'supervision_fees' => 'decimal:2',
        'total' => 'decimal:2',
        'load_capacity' => 'decimal:2',
        'eidc_synced_at' => 'datetime',
    ];

    /** هل هذه الوثيقة إجباري سيارات؟ */
    public function isMandatoryInsurance(): bool
    {
        return $this->insurance_type === 'تأمين إجباري سيارات';
    }

    /** هل تمت مزامنة الوثيقة مع الهيئة بنجاح؟ */
    public function isSyncedWithEidc(): bool
    {
        return $this->eidc_sync_status === 'synced' && !empty($this->eidc_policy_id);
    }

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->whereDate('end_date', '>=', \Carbon\Carbon::now()->toDateString());
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('end_date')
            ->whereDate('end_date', '<', \Carbon\Carbon::now()->toDateString());
    }
}
