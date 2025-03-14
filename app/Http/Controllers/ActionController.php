<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionResource;
use App\Models\Action;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActionController extends Controller
{
    public function getProductActions($id)
    {
        $product = Product::find($id);
        if(!$product){
            return response()->json([
                'message' => "Product not found"
            ], 404);
        }
        return ActionResource::collection($product->actions);
    }

    public function getUserActions($id = null){
        $user = $id ? User::find($id) : Auth::user();
        $actions = Action::where('user_id', $user->id)->latest()->paginate(10);
        return ActionResource::collection($actions);
    }
}
