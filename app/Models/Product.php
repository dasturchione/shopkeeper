<?php

namespace App\Models;

use App\Services\CalculatorService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'category_id',
        'supplier_id',
        'receiver_id',
        'barcode',
        'condition',
        'name',
        'in_price',
        'sale_price',
        'quantity',
        'warranty',
        'warranty_type',
        'note',
        'store_id',
        'is_active',
        'created_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function actions()
    {
        return $this->morphMany(Action::class, 'actionable');
    }

    public function getIdName($relation)
    {
        if (!$this->$relation) return null;
        return [
            'id'  => $this->$relation->id,
            'name'  => $this->$relation->name,
        ];
    }

    public function getRateAttribute()
    {
        return optional($this->store)->course->rate ?? 1;
    }

    public function getPrice($relation)
    {
        return [
            'main'  => $this->$relation,
            'convert'  => CalculatorService::convertPrice($this->$relation, $this->rate),
        ];
    }

}
