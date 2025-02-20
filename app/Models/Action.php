<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Action extends Model
{
    protected $fillable = ['actionable_id', 'actionable_type', 'action_type', 'data', 'user_id', 'store_id'];
}
