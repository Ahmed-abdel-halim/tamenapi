<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'quantity',
        'warehouse_location'
    ];

    public function item()
    {
        return $this->belongsTo(StoreItem::class, 'item_id');
    }
}
