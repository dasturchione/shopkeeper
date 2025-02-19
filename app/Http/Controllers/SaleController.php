<?php

namespace App\Http\Controllers;

use App\Http\Resources\SaleGroupResource;
use App\Models\Product;
use App\Models\SoldGroup;
use App\Models\SoldItem;
use App\Models\Store;
use App\Services\PermissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SaleController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index(){
        $response = $this->permissionService->hasPermission('sale', 'add');

        if ($response) {
            return $response;
        }

        $search = request()->query('search');

        $query = SoldGroup::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (preg_match('/\d{4}-\d{2}-\d{2}/', $search, $match)) {
                    $date = Carbon::parse($match[0])->format('Y-m-d');
                    $q->orWhereDate('created_at', $date);
                }
            })
            ->orWhereHas('vendor', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            })
            ->orWhereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%');
            });
        }

        $sold = $query->latest()->paginate(10);

        return SaleGroupResource::collection($sold);
    }

    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('sale', 'add');

        if ($response) {
            return $response;
        }
        DB::beginTransaction();
        try {
            // Validatsiya qilish
            $validator = Validator::make($request->all(), [
                'status'                => 'required|integer',
                'client'                => 'required|integer',
                'payment_type'          => 'integer',
                'discription'           => 'string',
                'items'                 => 'required|array',
                'items.*.product_id'    => 'required',
                'items.*.quantity'      => 'required|integer|min:1',
                'items.*.discount'      => 'required',
                'items.*.warranty'      => 'required',
                'items.*.warranty_type' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $shop = Store::where('id', $user->store_id)->first();

            $s_group = SoldGroup::create([
                'vendor_id'        => $user->id,
                'client_id'     => $request->client,
                'status'        => $request->status,
                'store_id'      => $user->store_id,
                'course_id'     => $shop->course_id,
                'payment_type'  => $request->payment_type,
                'note'          => $request->discription
            ]);

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Product not found"
                    ], 422);
                }

                SoldItem::create([
                    'product_id'    => $item['product_id'],
                    'in_price'      => $product->in_price,
                    'sale_price'    => $product->sale_price,
                    'quantity'      => $item['quantity'],
                    'warranty'      => $item['warranty'],
                    'warranty_type' => $item['warranty_type'],
                    'discount'      => $item['discount'],
                    'sold_group_id' => $s_group->id
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => "Create success",
                'data'    => $s_group
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

    public function edit(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('sale', 'add');

        if ($response) {
            return $response;
        }

        try {
            $validator = Validator::make($request->all(), [
                'status'                => 'required|integer',
                'client'                => 'required|integer',
                'payment_type'          => 'integer',
                'discription'           => 'string',
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
                    'client_id'     => $request->client,
                    'status'        => $request->status,
                    'store_id'      => $user->store_id,
                    'course_id'     => $shop->course_id,
                    'payment_type'  => $request->payment_type,
                    'note'          => $request->discription
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
}
