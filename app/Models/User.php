<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'is_admin',
        'authorized_documents',
        'salary',
        'national_id_number',
        'job_title',
        'profile_photo_path',
        'personal_id_proof_path',
        'employment_contract_path',
        'full_name_quad',
        'mother_name',
        'gender',
        'birth_date',
        'birth_place',
        'nationality',
        'social_status',
        'qualification',
        'blood_type',
        'personal_phone',
        'guardian_phone',
        'address',
        'financial_number',
        'job_number',
        'bank_name',
        'bank_branch',
        'account_number',
        'start_date',
        'working_hours_from',
        'working_hours_to',
        'working_days_from',
        'working_days_to',
        'contract_type',
        'contract_conditions',
        'housing_allowance',
        'transportation_allowance',
        'communication_allowance',
        'fixed_bonuses',
        'fixed_fines',
        'hourly_leave_deduction',
        'daily_leave_deduction',
        'national_id_photo_path',
        'identity_proof_path',
        'certified_stamp_path',
        'approved_signature_path',
        'educational_certificate_path',
        'health_certificate_path',
        'contract_conditions_photo_path',
        'is_active',
        'is_blocked',
        'salary_type',
        'hourly_rate',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'profile_photo_path',
        'personal_id_proof_path',
        'employment_contract_path',
        'national_id_photo_path',
        'identity_proof_path',
        'certified_stamp_path',
        'approved_signature_path',
        'educational_certificate_path',
        'health_certificate_path',
        'contract_conditions_photo_path',
    ];

    protected $appends = [
        'profile_photo_url',
        'personal_id_proof_url',
        'employment_contract_url',
        'national_id_photo_url',
        'identity_proof_url',
        'certified_stamp_url',
        'approved_signature_url',
        'educational_certificate_url',
        'health_certificate_url',
        'contract_conditions_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_admin' => 'boolean',
        'authorized_documents' => 'array',
        'birth_date' => 'date',
        'start_date' => 'date',
        'salary' => 'decimal:2',
        'housing_allowance' => 'decimal:2',
        'transportation_allowance' => 'decimal:2',
        'communication_allowance' => 'decimal:2',
        'fixed_bonuses' => 'decimal:2',
        'fixed_fines' => 'decimal:2',
        'hourly_leave_deduction' => 'decimal:2',
        'daily_leave_deduction' => 'decimal:2',
        'is_active' => 'boolean',
        'is_blocked' => 'boolean',
        'salary_type' => 'string',
        'hourly_rate' => 'decimal:2',
    ];

    /**
     * Get the branch agent associated with the user.
     */
    public function branchAgent()
    {
        return $this->hasOne(BranchAgent::class, 'user_id');
    }

    public function employeeRequests()
    {
        return $this->hasMany(EmployeeRequest::class, 'user_id');
    }

    public function custodies()
    {
        return $this->morphMany(FixedCustody::class, 'recipient');
    }

    public function payrolls()
    {
        return $this->hasMany(EmployeePayroll::class, 'user_id');
    }

    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class, 'user_id');
    }

    /**
     * Path relative to site root so the SPA (Vite) can proxy /storage → Laravel.
     * Avoids APP_URL on :8000 breaking images when the app runs on :5173.
     */
    protected function storagePublicUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        $path = str_replace('\\', '/', ltrim($path, '/'));

        return '/storage/'.$path;
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->profile_photo_path);
    }

    public function getPersonalIdProofUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->personal_id_proof_path);
    }

    public function getEmploymentContractUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->employment_contract_path);
    }

    public function getNationalIdPhotoUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->national_id_photo_path);
    }

    public function getIdentityProofUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->identity_proof_path);
    }

    public function getCertifiedStampUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->certified_stamp_path);
    }

    public function getApprovedSignatureUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->approved_signature_path);
    }

    public function getEducationalCertificateUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->educational_certificate_path);
    }

    public function getHealthCertificateUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->health_certificate_path);
    }

    public function getContractConditionsPhotoUrlAttribute(): ?string
    {
        return $this->storagePublicUrl($this->contract_conditions_photo_path);
    }
}
