<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = [
        'name',
        'note',
        'store_id',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
