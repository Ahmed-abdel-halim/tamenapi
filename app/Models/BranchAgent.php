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
        'renewal_date',
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
        'points_balance',
        'wallet_balance',
        'referral_code',
        'referred_by_id',
        'show_on_landing',
        'office_facade_photo',
        'office_phone',
        'office_location',
    ];

    protected $casts = [
        'contract_date' => 'date',
        'renewal_date' => 'date',
        'contract_end_date' => 'date',
        'consumed_custodies' => 'array',
        'fixed_custodies' => 'array',
        'authorized_documents' => 'array',
        'document_percentages' => 'array',
        'requested_documents' => 'array',
        'points_balance' => 'integer',
        'wallet_balance' => 'decimal:2',
        'show_on_landing' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function users()
    {
        return $this->hasMany(User::class, 'branch_agent_id');
    }

    public function posMachines()
    {
        return $this->belongsToMany(PosMachine::class, 'agent_pos_machine', 'branch_agent_id', 'pos_machine_id');
    }

    public function walletTransactions()
    {
        return $this->hasMany(AgentWalletTransaction::class, 'branch_agent_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(AgentWithdrawal::class, 'branch_agent_id');
    }

    public function referredAgents()
    {
        return $this->hasMany(BranchAgent::class, 'referred_by_id');
    }

    public function referrer()
    {
        return $this->belongsTo(BranchAgent::class, 'referred_by_id');
    }
}
