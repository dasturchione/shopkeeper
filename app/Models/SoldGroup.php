<?php

namespace App\Models;

use App\Services\CalculatorService;
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
        'course_id',
        'is_real'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'is_real' => 'boolean',
        ];
    }

    public function items()
    {
        return $this->hasMany(SoldItem::class, 'sold_group_id');
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function getIdName($relation)
    {
        return [
            'id'    => $this->$relation->id,
            'name'  => $this->$relation->name,
        ];
    }

    public function getItemsInfoAttribute()
    {
        return CalculatorService::calculateItemsInfo($this->items, $this->maincurrency, $this->convertcurrency, $this->course->rate ?? 1);
    }
}
