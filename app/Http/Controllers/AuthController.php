<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function loginAdmin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Xatoliklarni qaytarish
        }

        $user = User::where('email', $request->email)->first();
        if (!$user || !$user->is_active || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Login failed. Invalid credentials.'], 401);
        }

        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message' => 'Admin login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'role'       => $user->role->name,
            'name'       => $user->name,
            'image_path'    => $user->image_path ? url('storage/' . $user->image_path) : url('storage/user_images/user_image.png'),
            'permissions' => $user->permissions,
        ]);
    }

    public function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Logged out'
        ]);
    }
}




