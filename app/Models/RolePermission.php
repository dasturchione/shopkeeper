<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    protected $casts = [
        'can_view' => 'boolean',
        'can_add' => 'boolean',
        'can_edit' => 'boolean',
        'can_delete' => 'boolean',
    ];
}
