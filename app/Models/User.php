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
    ];

    protected $appends = [
        'profile_photo_url',
        'personal_id_proof_url',
        'employment_contract_url',
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
    ];

    /**
     * Get the branch agent associated with the user.
     */
    public function branchAgent()
    {
        return $this->hasOne(BranchAgent::class, 'user_id');
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
}
