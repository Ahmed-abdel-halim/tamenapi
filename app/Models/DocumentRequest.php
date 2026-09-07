<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_code',
        'branch_agent_id',
        'user_id',
        'applicant_name',
        'request_type',
        'document_type',
        'document_id',
        'document_number',
        'insured_name',
        'subject',
        'cancellation_reason',
        'cancellation_reason_other',
        'description',
        'notes',
        'legal_acknowledged',
        'status',
        'admin_message',
        'reviewed_by',
        'reviewed_at'
    ];

    protected $casts = [
        'legal_acknowledged' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    public function branchAgent()
    {
        return $this->belongsTo(BranchAgent::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
