<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClaimReport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function claim()
    {
        return $this->belongsTo(Claim::class);
    }
}
