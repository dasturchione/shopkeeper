<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function getPermissionsAttribute()
    {
        // Barcha ruxsatlar va role_permissionsni olish
        $allPermissions = \App\Models\Permission::all();
        $rolePermissions = \App\Models\RolePermission::where('role_id', $this->role_id)->get()->keyBy('permission_id');

        $permissions = [];

        // Agar foydalanuvchi superadmin bo'lsa, barcha ruxsatlar true bo'ladi
        if ($this->role->is_superadmin) {
            foreach ($allPermissions as $permission) {
                $permissions[$permission->short_code] = [
                    'can_view' => true,
                    'can_edit' => true,
                    'can_add' => true,
                    'can_delete' => true,
                ];
            }
        } else {
            foreach ($allPermissions as $permission) {
                $rolePermission = $rolePermissions->get($permission->id);

                // Har bir permission uchun ruxsatlar belgilash
                $permissions[$permission->short_code] = [
                    'can_view' => $rolePermission ? (bool) $rolePermission->can_view : false,
                    'can_edit' => $rolePermission ? (bool) $rolePermission->can_edit : false,
                    'can_add' => $rolePermission ? (bool) $rolePermission->can_add : false,
                    'can_delete' => $rolePermission ? (bool) $rolePermission->can_delete : false,
                ];
            }
        }

        return $permissions;
    }

}
