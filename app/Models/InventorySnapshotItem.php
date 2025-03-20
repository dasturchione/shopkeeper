<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySnapshotItem extends Model
{
    protected $fillable = [
        'inventory_snapshot_group_id',
        'product_id',
        'base_quantity',
        'stock_quantity',
        'not_selected',
    ];

    protected $casts = [
        'not_selected' => 'boolean',
    ];

    public function group()
    {
        return $this->belongsTo(InventorySnapshotGroup::class, 'inventory_snapshot_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
