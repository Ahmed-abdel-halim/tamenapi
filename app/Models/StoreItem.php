<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'serial_prefix',
        'category',
        'inventory_type',
        'unit',
        'price',
        'description',
        'min_threshold'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function stocks()
    {
        return $this->hasMany(InventoryStock::class, 'item_id');
    }

    public function custodies()
    {
        return $this->hasMany(FixedCustody::class, 'item_id');
    }
}
