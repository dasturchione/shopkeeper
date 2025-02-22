<?php

namespace App\Http\Controllers;

use App\Http\Resources\SoldItemResource;
use App\Models\SoldGroup;
use App\Models\Store;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SoldController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
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
                'status'                => 'required|integer',
                'client'                => 'required|integer',
                'payment_type'          => 'integer',
                'maincurrency'          => 'integer',
                'convertcurrency'       => 'integer',
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
                    'vendor_id'     => $user->id,
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

    public function soldItems($id){
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
}
