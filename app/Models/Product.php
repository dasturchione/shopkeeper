<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id', 'category_id', 'supplier_id', 'receiver_id', 'condition',
        'name', 'in_price', 'sale_price', 'quantity', 'warranty', 'warranty_type',
        'note', 'store_id', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }
    public function getBrandsAttribute(){
        return [
            'id'  => $this->brand->id,
            'name'  => $this->brand->name,
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function getCategorysAttribute(){
        return [
            'id'  => $this->category->id,
            'name'  => $this->category->name,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
    public function getReceiversAttribute(){
        return [
            'id'  => $this->user->id,
            'name'  => $this->user->name,
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function getSuppliersAttribute(){
        return [
            'id'  => $this->supplier->id,
            'name'  => $this->supplier->name,
        ];
    }

    public function getInPricesAttribute(){
        $rate = $this->store->course->rate;
        $somprice = ($this->in_price * $rate);
        return [
            'main'  => $this->in_price,
            'convert'  => $somprice,
        ];
    }

    public function getSalePricesAttribute(){
        $rate = $this->store->course->rate;
        $somprice = ($this->sale_price * $rate);
        return [
            'main'  => $this->sale_price,
            'convert'  => $somprice,
        ];
    }

    public function store(){
        return $this->belongsTo(Store::class, 'store_id');
    }

}
