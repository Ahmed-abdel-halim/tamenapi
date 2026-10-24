<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $table = 'branches';

    protected $fillable = [
        'code',
        'name',
        'city',
        'address',
        'phone',
        'email',
        'manager_name',
        'manager_phone',
        'manager_user_id',
        'status',
        'is_main_branch',
        'commercial_registration',
        'tax_number',
        'notes',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
    ];

    /**
     * Get the manager user account for this branch.
     */
    public function managerUser()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    /**
     * Get the users/employees assigned to this branch.
     */
    public function users()
    {
        return $this->hasMany(User::class, 'branch_id');
    }

    /**
     * Get agents affiliated with this branch.
     */
    public function agents()
    {
        return $this->hasMany(BranchAgent::class, 'branch_id');
    }

    /**
     * Get the compulsory/third-party documents issued under this branch.
     */
    public function insuranceDocuments()
    {
        return $this->hasMany(InsuranceDocument::class, 'branch_id');
    }

    /**
     * Get international insurance documents.
     */
    public function internationalInsuranceDocuments()
    {
        return $this->hasMany(InternationalInsuranceDocument::class, 'branch_id');
    }

    /**
     * Get travel insurance documents.
     */
    public function travelInsuranceDocuments()
    {
        return $this->hasMany(TravelInsuranceDocument::class, 'branch_id');
    }

    /**
     * Get resident insurance documents.
     */
    public function residentInsuranceDocuments()
    {
        return $this->hasMany(ResidentInsuranceDocument::class, 'branch_id');
    }

    /**
     * Scope active branches.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'نشط');
    }
}
