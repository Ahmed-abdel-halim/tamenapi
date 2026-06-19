<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageService extends Model
{
    protected $fillable = [
        'title_ar',
        'title_en',
        'desc_ar',
        'desc_en',
        'icon',
        'image_url',
        'link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
