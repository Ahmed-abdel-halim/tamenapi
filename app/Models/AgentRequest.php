<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'user_id',
        'type',
        'priority',
        'subject',
        'message',
        'status',
        'admin_notes',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'json',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
