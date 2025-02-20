<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActionResource;
use App\Models\Product;
use Illuminate\Http\Request;

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
}
