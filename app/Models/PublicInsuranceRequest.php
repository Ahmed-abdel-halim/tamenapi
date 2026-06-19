<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicInsuranceRequest extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'whatsapp',
        'email',
        'insurance_type',
        'request_type',
        'previous_policy_number',
        'payment_method',
        'notes',
        'attachment_url',
        'status',
        'admin_notes',
    ];
}
