<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustodyMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'recipient_id',
        'recipient_type',
        'quantity',
        'type', // issue, return, loss, damage
        'processed_by', // User ID of the finance employee
        'notes'
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'item_id');
    }

    public function recipient()
    {
        return $this->morphTo();
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
