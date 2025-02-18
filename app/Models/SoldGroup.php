<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SoldGroup extends Model
{
    protected $fillable = [
        'vendor',
        'client_id',
        'note',
        'store_id',
        'status',
        'payment_type',
        'maincurrency',
        'convertcurrency',
        'course_id'
    ];
}
