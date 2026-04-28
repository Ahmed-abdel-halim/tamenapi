<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Claim extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function document()
    {
        return $this->morphTo();
    }

    public function reports()
    {
        return $this->hasMany(ClaimReport::class);
    }

    public function transfers()
    {
        return $this->hasMany(ClaimTransfer::class);
    }
}
