<?php

namespace App\Http\Controllers;

use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $response = $this->permissionService->hasPermission('brand', 'view');

        if ($response) {
            return $response;
        }
        $search = request()->query('search');

        $query = Brand::query();

        // Agar 'name' parametri mavjud bo'lsa, qidiruv qo'llaniladi
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        $brands = $query->latest()->paginate(10);
        return BrandResource::collection($brands);
    }

    public function all()
    {
        $response = $this->permissionService->hasPermission('brand', 'view');

        if ($response) {
            return $response;
        }

        $query = Brand::latest()->paginate(10);
        return BrandResource::collection($query);
    }

    // Create a new brand
    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('brand', 'add');

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
        $brand = Brand::create([
            'name'      => $request->name,
            'note'      => $request->note ? $request->note : null,
            'store_id'  => $user->store_id,
        ]);
        return response()->json($brand, 201);
    }

    // Show a specific brand
    public function show($id)
    {
        $response = $this->permissionService->hasPermission('brand', 'view');

        if ($response) {
            return $response;
        }

        $brand = Brand::find($id);
        if(!$brand){
            return response()->json(['error' => 'Brand not found'], 404);
        }
        return new BrandResource($brand);
    }

    // Update an existing brand
    public function update(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('brand', 'edit');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'note' => 'nullable|string|max:250',
            'is_active' => 'boolean',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Xatoliklarni qaytarish
        }

        $brand = Brand::findOrFail($id);
        $brand->name = $request->name;
        $brand->note = $request->note;
        $brand->update();
        return response()->json($brand);
    }

    // Delete a brand
    public function destroy($id)
    {
        $response = $this->permissionService->hasPermission('brand', 'delete');
        if ($response) {
            return $response;
        }

        try {
            // Ma'lumotni topish
            $brand = Brand::findOrFail($id);

            // O'chirish
            $brand->delete();

            return response()->json([
                'message' => 'Brand deleted successfully'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Cannot delete brand. It is being used in other records.',
                'details' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the brand',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
