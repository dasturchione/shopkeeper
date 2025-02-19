<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldGroup extends Model
{
    protected $fillable = [
        'vendor_id',
        'client_id',
        'note',
        'store_id',
        'status',
        'payment_type',
        'maincurrency',
        'convertcurrency',
        'course_id'
    ];

    public function items()
    {
        return $this->hasMany(SoldItem::class, 'sold_group_id');
    }

    public function vendor(){
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function getVendorInfoAttribute(){
        return [
            'id'    => $this->vendor->id,
            'name'  => $this->vendor->name,
        ];
    }

    public function client(){
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function getClientInfoAttribute(){
        return [
            'id'    => $this->client->id,
            'name'  => $this->client->name,
        ];
    }

    public function getItemsInfoAttribute()
    {
        // Yig‘ishning eng samarali usuli va discountni chiqarish
        $total_price = $this->items->sum(function ($item) {
            // sale_price * quantity, discountni chiqarib tashlash
            return ($item->sale_price * $item->quantity) - $item->discount;
        });

        // Total quantityni yig‘ish
        $total_quantity = $this->items->sum('quantity');

        return [
            'total_price'      => $total_price,
            'total_quantity'   => $total_quantity,
        ];
    }
}
