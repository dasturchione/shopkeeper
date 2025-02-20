<?php

namespace App\Http\Controllers;

use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $response = $this->permissionService->hasPermission('supplier', 'view');

        if ($response) {
            return $response;
        }
        $search = request()->query('search');
        $user = Auth::user();
        $query = Supplier::query()->where('store_id', $user->store_id);

        // Agar 'name' parametri mavjud bo'lsa, qidiruv qo'llaniladi
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        $suppliers = $query->latest()->paginate(10);
        return SupplierResource::collection($suppliers);
    }

    public function all()
    {
        $response = $this->permissionService->hasPermission('supplier', 'view');

        if ($response) {
            return $response;
        }
        $user = Auth::user();
        $suppliers = Supplier::query()->where('store_id', $user->store_id)->latest()->get();
        return SupplierResource::collection($suppliers);
    }

    public function show($id)
    {
        $response = $this->permissionService->hasPermission('supplier', 'view');

        if ($response) {
            return $response;
        }

        $user = Auth::user();

        $suppliers = Supplier::where('store_id', $user->store_id)->find($id);
        if (!$suppliers) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }
        return new SupplierResource($suppliers);
    }

    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('supplier', 'add');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string',
            'note'      => 'nullable|string|max:250',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $client = Supplier::create([
            'name'      => $request->name,
            'surname'   => $request->surname ? $request->surname : null,
            'phone'     => $request->phone,
            'note'      => $request->note ? $request->note : null,
            'store_id'  => $user->store_id,
        ]);
        return response()->json($client, 201);
    }

    public function update(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('supplier', 'edit');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'note' => 'nullable|string|max:250',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $client = Supplier::where('store_id', $user->store_id)->find($id);

        if ($client) {
            $client->update([
                'name'      => $request->name,
                'surname'   => $request->surname,
                'phone'     => $request->phone,
                'note'      => $request->note ? $request->note : null,
                'store_id'  => $user->store_id,
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Client not found!'
            ], 404);
        }
        return response()->json($client, 201);
    }

    public function destroy($id)
    {
        $response = $this->permissionService->hasPermission('supplier', 'delete');
        if ($response) {
            return $response;
        }

        try {
            $user = Auth::user();
            $category = Supplier::where('store_id', $user->store_id)->find($id);

            if(!$category){
                return response()->json([
                    'message' => 'Supplier not found'
                ], 404);
            }
            $category->delete();

            return response()->json([
                'message' => 'Supplier deleted successfully'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Cannot delete Supplier. It is being used in other records.',
                'details' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the Supplier',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
