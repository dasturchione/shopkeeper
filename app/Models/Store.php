<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'name',
    ];

    public function course(){
        return $this->belongsTo(Course::class, 'course_id');
    }
}
