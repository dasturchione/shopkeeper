<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'phone',
        'note',
        'is_active',
        'store_id'
    ];
}
