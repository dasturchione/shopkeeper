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

    public function index()
    {
        $response = $this->permissionService->hasPermission('sold_goods', 'view');

        if ($response) {
            return $response;
        }

        $search = request()->query('search');

        $user = Auth::user();
        $query = SoldGroup::query()->where('store_id', $user->store_id);

        if ($search) {
            $query->where(function ($q) use ($search) {
                // Sana formatini tekshirish va qidirish
                if (preg_match('/\d{4}-\d{2}-\d{2}/', $search, $match)) {
                    $date = Carbon::parse($match[0])->format('Y-m-d');
                    $q->orWhereDate('created_at', $date);
                }

                // ID bo‘yicha qidirish (raqam bo‘lsa)
                if (is_numeric($search)) {
                    $q->orWhere('id', $search);
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
                'payment_status'       => 'required|integer',
                'client'               => 'required|integer',
                'payment_type'         => 'integer',
                'discription'          => 'string',
                'cart'                 => 'required|array',
                'cart.*.product_id'    => 'required',
                'cart.*.quantity'      => 'required|integer|min:1',
                'cart.*.discount'      => 'required',
                'cart.*.warranty'      => 'required',
                'cart.*.warranty_type' => 'required'
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
                'vendor_id'     => $user->id,
                'client_id'     => $request->client,
                'status'        => $request->payment_status,
                'store_id'      => $user->store_id,
                'course_id'     => $shop->course_id,
                'maincurrency' => $request->main_currency ? $request->main_currency : null,
                'convertcurrency' => $request->convert_currency ? $request->convert_currency : null,
                'payment_type'  => $request->payment_type,
                'is_real'       => $request->is_real,
                'note'          => $request->discription
            ]);

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
                    'sold_group_id' => $s_group->id
                ]);

                $product->actions()->create([
                    'action_type' => 'sale_product',
                    'data' => json_encode([
                        'product_id'    => $product->id,
                        'quantity'      => $item['quantity'],
                        'warranty'      => $item['warranty'],
                        'warranty_type' => $item['warranty_type'],
                        'discount'      => $item['discount'],
                        'sold_group_id' => $s_group->id
                    ]),
                    'user_id' => $user->id,
                    'store_id' => $user->store_id
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

}
