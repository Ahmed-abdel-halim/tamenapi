<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedCustody extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'quantity',
        'serial_start',
        'serial_end',
        'batch_ref',
        'recipient_id',
        'recipient_type',
        'assigned_at',
        'return_due_at',
        'condition',
        'status',
        'notes'
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'return_due_at' => 'date'
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'item_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }
}
