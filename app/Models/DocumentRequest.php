<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_agent_id',
        'user_id',
        'request_type',
        'document_type',
        'document_number',
        'subject',
        'description',
        'status',
        'admin_message'
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
