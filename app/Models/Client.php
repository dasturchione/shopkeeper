<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'surname',
        'phone',
        'note',
        'is_active',
        'store_id'
    ];

    public function actions()
    {
        return $this->morphMany(Action::class, 'actionable');
    }
}
