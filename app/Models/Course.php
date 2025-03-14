<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Course extends Model
{
    use HasFactory;
    protected $table = 'courses';

    protected $fillable = [
        'rate',
        'main_id',
        'convert_id',
        'store_id',
    ];

    public function isActive()
    {
        $userStoreId = Auth::user()->store_id;

        return DB::table('stores')
            ->where('id', $userStoreId)
            ->where('course_id', $this->id)
            ->exists();
    }
}
