<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaPost extends Model
{
    protected $fillable = [
        'type',
        'title_ar',
        'title_en',
        'content_ar',
        'content_en',
        'media_url',
        'images',
        'location_ar',
        'location_en',
        'views',
        'sort_order',
        'is_active',
        'published_date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'views' => 'integer',
        'sort_order' => 'integer',
        'published_date' => 'datetime',
        'images' => 'array',
    ];
}
