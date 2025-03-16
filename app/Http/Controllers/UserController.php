<?php

namespace App\Http\Controllers;

use App\Http\Resources\SessionResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class UserController extends Controller
{
    public function index()
    {
        $users = User::get();

        return UserResource::collection($users);
    }

    public function show($id = null)
    {
        $user = $id ? User::find($id) : Auth::user();
        return new UserResource($user);
    }

    public function sessions($id = null)
    {
        $user = $id ? User::find($id) : Auth::user();
        $tokens = PersonalAccessToken::where('tokenable_id', $user->id)
            ->where('tokenable_type', get_class($user))
            ->latest()
            ->paginate(20);

        return SessionResource::collection($tokens);
    }

    public function permissions() {
        $user = Auth::user();
        return response()->json([
            'data' => $user->permissions
        ]);
    }
}
