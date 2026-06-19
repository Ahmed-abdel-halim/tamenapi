<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomepageSlider extends Model
{
    protected $fillable = [
        'media_type',
        'media_url',
        'title_ar',
        'title_en',
        'subtitle_ar',
        'subtitle_en',
        'button_text_ar',
        'button_text_en',
        'button_link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
