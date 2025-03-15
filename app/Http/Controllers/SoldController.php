<?php

namespace App\Http\Controllers;

use App\Http\Resources\SoldItemResource;
use App\Models\Product;
use App\Models\SoldGroup;
use App\Models\SoldItem;
use App\Models\Store;
use App\Services\PermissionService;
use App\Services\CalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SoldController extends Controller
{
    protected $calculator;
    protected $permissionService;

    public function __construct(CalculatorService $calculator, PermissionService $permissionService)
    {
        $this->calculator = $calculator;
        $this->permissionService = $permissionService;
    }

    public function edit(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('sold_goods', 'edit');

        if ($response) {
            return $response;
        }

        try {
            $validator = Validator::make($request->all(), [
                'payment_status'  => 'required|boolean',
                'payment_type'    => 'nullable|integer|required_if:payment_status,1',
                'convert'        => 'integer',
                'discription'    => 'string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $shop = Store::where('id', $user->store_id)->first();

            $s_group = SoldGroup::find($id);

            if ($s_group) {
                // Yangilash
                $s_group->update([
                    'vendor_id'        => $user->id,
                    'client_id'        => $request->client ? $request->client : $s_group->client_id,
                    'status'           => $request->payment_status,
                    'payment_type'     => $request->payment_type,
                    'maincurrency'     => in_array($request->payment_type, [1, 3]) ? 0 : $request->main,
                    'convertcurrency'  => $request->payment_type == 2 ? 0 : $request->convert,
                    'note'             => $request->description
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'SoldGroup updated successfully!',
                    'data' => $s_group
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'SoldGroup not found!'
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error('Error occurred while creating order', [
                'request' => $request->all(),
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function addProduct(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('sold', 'edit');

        if ($response) {
            return $response;
        }
        DB::beginTransaction();
        try {
            DB::commit();
            $user = Auth::user();
            foreach ($request->cart as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Product not found"
                    ], 422);
                }

                $product->decrement('quantity', $item['quantity']);

                SoldItem::create([
                    'product_id'    => $item['product_id'],
                    'in_price'      => $product->in_price,
                    'sale_price'    => $product->sale_price,
                    'quantity'      => $item['quantity'],
                    'warranty'      => $item['warranty'],
                    'warranty_type' => $item['warranty_type'],
                    'discount'      => $item['discount'],
                    'sold_group_id' => $id
                ]);

                $product->actions()->create([
                    'action_type' => 'sale_product',
                    'data' => json_encode([
                        'product_id'    => $product->id,
                        'quantity'      => $item['quantity'],
                        'warranty'      => $item['warranty'],
                        'warranty_type' => $item['warranty_type'],
                        'discount'      => $item['discount'],
                        'sold_group_id' => $id
                    ]),
                    'user_id' => $user->id,
                    'store_id' => $user->store_id
                ]);
            }
            return response()->json([
                'message' => "Create success",
                'data'    => $id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error occurred while creating order', [
                'request' => $request->all(),
                'exception' => $e->getMessage(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function soldItems($id)
    {
        $response = $this->permissionService->hasPermission('sold_goods', 'view');

        if ($response) {
            return $response;
        }

        $soldGroup = SoldGroup::find($id);

        if (!$soldGroup) {
            return response()->json([
                'status' => 'error',
                'message' => 'SoldGroup not found!'
            ], 404);
        }

        return new SoldItemResource($soldGroup);
    }

    public function backProduct(Request $request)
    {
        $response = $this->permissionService->hasPermission('sold_goods', 'edit');

        if ($response) {
            return $response;
        }

        $user = Auth::user();

        $item = SoldItem::find($request->id);
        if ($item->product->condition == $request->condition) {
            $item->product->increment('quantity', $request->quantity);
            $item->decrement('quantity', $request->quantity);

            $item->product->actions()->create([
                'action_type' => 'back_product',
                'data' => json_encode([
                    'brand_id'      => $item->product->brand_id,
                    'category_id'   => $item->product->category_id,
                    'supplier_id'   => $item->product->supplier_id,
                    'receiver_id'   => $user->id,
                    'condition'     => $request->condition,
                    'name'          => $item->product->name,
                    'in_price'      => $item->product->in_price,
                    'sale_price'    => $item->product->sale_price,
                    'quantity'      => $request->quantity,
                    'warranty'      => $item->product->warranty,
                    'warranty_type' => $item->product->warranty_type,
                    'from'          => $item->sold_group_id,
                ]),
                'user_id' => $user->id,
                'store_id' => $user->store_id
            ]);

            if ($item->quantity == 0) {
                $item->delete();
            }
        } else {
            $product = Product::create([
                'brand_id'      => $item->product->brand_id,
                'category_id'   => $item->product->category_id,
                'supplier_id'   => $item->product->supplier_id,
                'receiver_id'   => $user->id,
                'barcode'       => $this->calculator->generateUniqueBarcode(),
                'condition'     => $request->condition,
                'name'          => $item->product->name,
                'in_price'      => $request->in_price,
                'sale_price'    => $request->sale_price,
                'quantity'      => $request->quantity,
                'warranty'      => $request->warranty,
                'warranty_type' => $request->warranty_type,
                'note'          => $request->note ? $request->note : null,
                'store_id'      => $user->store_id,
                'is_active'     => true,
            ]);

            $product->actions()->create([
                'action_type' => 'back_add_product',
                'data' => json_encode([
                    'brand_id'      => $item->product->brand_id,
                    'category_id'   => $item->product->category_id,
                    'supplier_id'   => $item->product->supplier_id,
                    'receiver_id'   => $user->id,
                    'condition'     => $request->condition,
                    'name'          => $item->product->name,
                    'in_price'      => $request->in_price,
                    'sale_price'    => $request->sale_price,
                    'quantity'      => $request->quantity,
                    'warranty'      => $request->warranty,
                    'warranty_type' => $request->warranty_type,
                    'from'          => $item->sold_group_id,
                ]),
                'user_id' => $user->id,
                'store_id' => $user->store_id
            ]);

            $item->decrement('quantity', $request->quantity);
            if ($item->quantity == 0) {
                $item->delete();
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Product returned successfully!'
        ]);
    }

    public function soldItemDiscount(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('sold_goods', 'edit');

        if ($response) {
            return $response;
        }

        $solditem = SoldItem::find($id);

        if (!$solditem) {
            return response()->json(['error' => 'Sold item not found'], 404);
        }

        $solditem->update([
            'discount' => $request->discount,
        ]);

        return response()->json(['message' => 'Discount updated successfully'], 200);
    }
}
