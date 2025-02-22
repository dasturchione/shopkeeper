<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldItem extends Model
{
    protected $fillable = [
        'product_id',
        'in_price',
        'sale_price', 
        'quantity',
        'discount',
        'warranty',
        'warranty_type',
        'sold_group_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
