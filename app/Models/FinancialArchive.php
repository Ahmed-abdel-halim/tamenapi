<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'document_name',
        'category',
        'file_path',
        'file_type',
        'file_size',
        'uploaded_by', // user_id or name
        'related_entity', // agent/bank name
        'status' // active, archived
    ];

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }
}

