<?php
namespace App\Services;

use App\Models\RolePermission;
use Illuminate\Support\Facades\Auth;

class PermissionService
{

    public function hasPermission($permission, $action)
    {
        $user = Auth::user();

        if ($user) {
            if (!$user->is_active) {
                return response()->json([
                    'error' => 'Foydalanuvchi faol emas.',
                ], 403);
            }

            $rolePermissions = RolePermission::where('role_id', $user->role_id)
                ->where('permission_id', $this->getPermissionId($permission))
                ->first();

            if ($rolePermissions) {
                // Har xil amallarni tekshirish
                switch ($action) {
                    case 'view':
                        if (!$rolePermissions->can_view) {
                            return response()->json([
                                'error' => 'Sizda ko‘rish huquqi yo‘q.',
                            ], 403);
                        }
                        break;

                    case 'add':
                        if (!$rolePermissions->can_add) {
                            return response()->json([
                                'error' => 'Sizda qo‘shish huquqi yo‘q.',
                            ], 403);
                        }
                        break;

                    case 'edit':
                        if (!$rolePermissions->can_edit) {
                            return response()->json([
                                'error' => 'Sizda tahrirlash huquqi yo‘q.',
                            ], 403);
                        }
                        break;

                    case 'delete':
                        if (!$rolePermissions->can_delete) {
                            return response()->json([
                                'error' => 'Sizda o‘chirish huquqi yo‘q.',
                            ], 403);
                        }
                        break;

                    default:
                        return response()->json([
                            'error' => 'Noma’lum amal.',
                        ], 403);
                }
            } elseif($this->getRole($user->role_id)->is_superadmin){
            } else {
                return response()->json([
                    'error' => 'Ruxsat mavjud emas.',
                ], 403);
            }
        }else{
            return response()->json([
                'error' => "User not found",
            ]);
        }
    }

    private function getPermissionId($permission)
    {
        $permissionModel = \App\Models\Permission::where('short_code', $permission)->first();
        return $permissionModel ? $permissionModel->id : 0;
    }

    private function getRole($id)
    {
        $permissionModel = \App\Models\Role::where('id', $id)->first();
        return $permissionModel ? $permissionModel : null;
    }
}
