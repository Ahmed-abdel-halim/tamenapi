<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLoyaltySetting extends Model
{
    use HasFactory;

    protected $table = 'agent_loyalty_settings';

    protected $fillable = [
        'policy_type',
        'display_name',
        'points_reward',
    ];

    protected $casts = [
        'points_reward' => 'integer',
    ];
}
