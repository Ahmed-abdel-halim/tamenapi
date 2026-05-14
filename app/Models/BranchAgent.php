<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchAgent extends Model
{
    use HasFactory;

    protected $table = 'branches_agents';

    protected $fillable = [
        'type',
        'code',
        'agency_name',
        'agent_name',
        'activity',
        'agency_number',
        'stamp_number',
        'contract_date',
        'contract_end_date',
        'contract_duration',
        'city',
        'address',
        'phone',
        'nationality',
        'national_id',
        'identity_number',
        'consumed_custodies',
        'fixed_custodies',
        'personal_photo',
        'identity_photo',
        'national_id_photo',
        'contract_photo',
        'passport_photo',
        'clearance_certificate',
        'non_bankruptcy_certificate',
        'experience_certificate',
        'non_employment_certificate',
        'tb_health_certificate',
        'academic_qualification',
        'activity_license',
        'user_id',
        'notes',
        'status',
        'authorized_documents',
        'document_percentages',
        'contract_conditions',
        'requested_documents',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'contract_end_date' => 'date',
        'consumed_custodies' => 'array',
        'fixed_custodies' => 'array',
        'authorized_documents' => 'array',
        'document_percentages' => 'array',
        'requested_documents' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
