<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClientResource;
use App\Models\Client;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $response = $this->permissionService->hasPermission('client', 'view');

        if ($response) {
            return $response;
        }

        $search = request()->query('search');

        $user = Auth::user();

        $query = Client::query()->where('store_id', $user->store_id);

        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $clients = $query->latest()->paginate(10);


        return ClientResource::collection($clients);
    }

    public function all()
    {
        $response = $this->permissionService->hasPermission('client', 'view');

        if ($response) {
            return $response;
        }
        $user = Auth::user();

        $query = Client::where('store_id', $user->store_id)->latest()->get();

        return ClientResource::collection($query);
    }

    public function show($id)
    {
        $response = $this->permissionService->hasPermission('client', 'view');

        if ($response) {
            return $response;
        }

        $user = Auth::user();

        $brand = Client::where('store_id', $user->store_id)->find($id);
        if (!$brand) {
            return response()->json(['error' => 'Client not found'], 404);
        }
        return new ClientResource($brand);
    }

    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('client', 'add');

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
        $client = Client::create([
            'name'      => $request->name,
            'phone'     => $request->phone,
            'note'      => $request->note ? $request->note : null,
            'store_id'  => $user->store_id,
        ]);
        return response()->json($client, 201);
    }

    public function update(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('client', 'edit');

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
        $client = Client::where('store_id', $user->store_id)->find($id);

        if ($client) {
            $client->update([
                'name'      => $request->name,
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
        $response = $this->permissionService->hasPermission('client', 'delete');
        if ($response) {
            return $response;
        }

        try {
            $user = Auth::user();
            $category = Client::where('store_id', $user->store_id)->find($id);

            if(!$category){
                return response()->json([
                    'message' => 'Client not found'
                ], 404);
            }
            $category->delete();

            return response()->json([
                'message' => 'Client deleted successfully'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Cannot delete client. It is being used in other records.',
                'details' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the client',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
