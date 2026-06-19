<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsuranceType extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',
        'description_ar',
        'description_en',
        'details_ar',
        'details_en',
        'icon',
        'color',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
