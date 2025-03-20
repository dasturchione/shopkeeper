<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventorySnapshotGroup extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function items()
    {
        return $this->hasMany(InventorySnapshotItem::class, 'inventory_snapshot_group_id');
    }
}
