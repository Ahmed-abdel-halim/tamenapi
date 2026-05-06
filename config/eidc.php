<?php

return [
    /*
    |--------------------------------------------------------------------------
    | EIDC Insurance Authority API
    |--------------------------------------------------------------------------
    | هيئة الإشراف على التأمين - ليبيا
    | https://in.eidc.gov.ly/swagger/index.html
    */

    'base_url' => env('EIDC_BASE_URL', 'https://in.eidc.gov.ly'),

    // بيانات الدخول للـ API
    'username' => env('EIDC_USERNAME', ''),
    'password' => env('EIDC_PASSWORD', ''),
    'api_key'  => env('EIDC_API_KEY', ''),

    // هل التكامل مفعل؟ (false = وضع محاكاة للتطوير)
    'enabled' => env('EIDC_ENABLED', true),
];
