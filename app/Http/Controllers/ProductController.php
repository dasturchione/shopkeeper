<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use App\Services\CalculatorService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProductController extends Controller
{

    protected $calculator;
    protected $permissionService;

    public function __construct(CalculatorService $calculator, PermissionService $permissionService)
    {
        $this->calculator = $calculator;
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $response = $this->permissionService->hasPermission('goods', 'view');

        if ($response) {
            return $response;
        }

        $search = request()->query('search');

        $query = Product::query()->where('quantity', '>', 0)->where('is_active', true);

        if ($search) {
            $keywords = explode(' ', trim($search));

            $query->where(function ($q) use ($keywords, $search) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', '%' . $word . '%');
                }
                $q->orWhere('barcode', 'like', '%' . $search . '%');
            });
        }

        $products = $query->latest()->paginate(10);

        return ProductResource::collection($products);
    }

    public function indexArchive()
    {
        $response = $this->permissionService->hasPermission('archive_goods', 'view');

        if ($response) {
            return $response;
        }

        $search = request()->query('search');

        $query = Product::query()->where(function ($q) {
            $q->where('quantity', '<', 1)->orWhere('is_active', false);
        });

        if (!empty($search)) {
            $keywords = explode(' ', trim($search));

            $query->where(function ($q) use ($keywords, $search) {
                foreach ($keywords as $word) {
                    $q->orWhere('name', 'like', '%' . $word . '%');
                }
                $q->orWhere('barcode', 'like', '%' . $search . '%');
            });
        }

        $products = $query->latest()->paginate(10);


        return ProductResource::collection($products);
    }

    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('goods', 'add');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categorys,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'condition' => 'required|string|in:new,openbox,used',
            'name' => 'required|string',
            'in_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'warranty' => 'required|integer',
            'warranty_type' => 'required|string|in:day,week,month,year',
            'note' => 'nullable|string',
            // 'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $product = Product::create([
            'brand_id'      => $request->brand_id,
            'category_id'   => $request->category_id,
            'supplier_id'   => $request->supplier_id,
            'receiver_id'   => $user->id,
            'barcode'       => $this->calculator->generateUniqueBarcode(),
            'condition'     => $request->condition,
            'name'          => $request->name,
            'in_price'      => $request->in_price,
            'sale_price'    => $request->sale_price,
            'quantity'      => $request->quantity,
            'warranty'      => $request->warranty,
            'warranty_type' => $request->warranty_type,
            'note'          => $request->note ? $request->note : null,
            'store_id'      => $user->store_id,
            'is_active'     => true,
            'created_at'    => Carbon::parse($request->date)->format('Y-m-d')
        ]);

        $product->actions()->create([
            'action_type' => 'add_product',
            'data' => json_encode([
                'brand_id'      => $request->brand_id,
                'category_id'   => $request->category_id,
                'supplier_id'   => $request->supplier_id,
                'receiver_id'   => $user->id,
                'condition'     => $request->condition,
                'name'          => $request->name,
                'in_price'      => $request->in_price,
                'sale_price'    => $request->sale_price,
                'quantity'      => $request->quantity,
                'warranty'      => $request->warranty,
                'warranty_type' => $request->warranty_type,
            ]),
            'user_id' => $user->id,
            'store_id' => $user->store_id
        ]);

        return response()->json([
            'message' => "Create success",
            'data'    => new ProductResource($product)
        ]);
    }

    public function update(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('goods', 'edit');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'brand_id' => 'required|exists:brands,id',
            'category_id' => 'required|exists:categorys,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'condition' => 'required|string|in:new,openbox,used',
            'name' => 'required|string',
            'in_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'quantity' => 'required|integer',
            'warranty' => 'required|integer',
            'warranty_type' => 'required|string|in:day,week,month,year',
            'note' => 'nullable|string',
            // 'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => "Product not found"
            ], 404);
        }

        $oldProduct = clone $product;

        $product->brand_id      = $request->brand_id;
        $product->category_id   = $request->category_id;
        $product->supplier_id   = $request->supplier_id;
        $product->receiver_id   = $user->id;
        $product->condition     = $request->condition;
        $product->name          = $request->name;
        $product->in_price      = $request->in_price;
        $product->sale_price    = $request->sale_price;
        $product->quantity      = $request->quantity;
        $product->warranty      = $request->warranty;
        $product->warranty_type = $request->warranty_type;
        $product->note          = $request->note ? $request->note : null;
        $product->store_id      = $user->store_id;
        $product->created_at    = Carbon::parse($request->date)->format('Y-m-d');
        $product->is_active     = true;

        $product->update();

        $product->actions()->create([
            'action_type' => 'edit_product',
            'data' => json_encode([
                'old' => [
                    'brand_id'      => $oldProduct->brand_id,
                    'category_id'   => $oldProduct->category_id,
                    'supplier_id'   => $oldProduct->supplier_id,
                    'receiver_id'   => $oldProduct->receiver_id,
                    'condition'     => $oldProduct->condition,
                    'name'          => $oldProduct->name,
                    'in_price'      => $oldProduct->in_price,
                    'sale_price'    => $oldProduct->sale_price,
                    'quantity'      => $oldProduct->quantity,
                    'warranty'      => $oldProduct->warranty,
                    'warranty_type' => $oldProduct->warranty_type
                ],

                'new' => [
                    'brand_id'      => $request->brand_id,
                    'category_id'   => $request->category_id,
                    'supplier_id'   => $request->supplier_id,
                    'receiver_id'   => $user->id,
                    'condition'     => $request->condition,
                    'name'          => $request->name,
                    'in_price'      => $request->in_price,
                    'sale_price'    => $request->sale_price,
                    'quantity'      => $request->quantity,
                    'warranty'      => $request->warranty,
                    'warranty_type' => $request->warranty_type,
                ]
            ]),
            'user_id' => $user->id,
            'store_id' => $user->store_id
        ]);

        return response()->json([
            'message' => "Update success",
            'data'    => new ProductResource($product)
        ]);
    }

    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => "Product not found"
            ], 404);
        }

        return new ProductResource($product);
    }

    public function showBarcode($id)
    {
        $product = Product::where('barcode', $id)->where('quantity', '>', 0)->where('is_active', true)->first();
        if (!$product) {
            return response()->json([
                'error' => "Product not found"
            ], 404);
        }

        return new ProductResource($product);
    }

    public function destroy($id)
    {
        $response = $this->permissionService->hasPermission('goods', 'delete');

        if ($response) {
            return $response;
        }

        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'error' => "Product not found"
            ], 404);
        }

        try {
            $product->delete();
            return response()->json([
                'message' => "Delete success"
            ]);
        } catch (\Exception $e) {
            $product->is_active = false;
            $product->save();
            return response()->json([
                'message' => "Product could not be deleted, set to inactive"
            ]);
        }
    }

    public function barcodegen()
    {
        $barcode = $this->calculator->generateUniqueBarcode();

        return response()->json(['barcode' => $barcode]);
    }

    public function addBarcodesToProducts()
    {
        $products = Product::whereNull('barcode')->get();

        foreach ($products as $product) {
            $product->update([
                'barcode' => $this->calculator->generateUniqueBarcode()
            ]);
        }

        return "Barcha mahsulotlarga barcode qo‘shildi!";
    }
}
