<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarineEngineModel extends Model
{
    use HasFactory;

    protected $table = 'marine_engine_models';

    protected $fillable = [
        'name',
    ];
}
