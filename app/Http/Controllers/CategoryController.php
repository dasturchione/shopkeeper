<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\PermissionService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\BrandResource;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $response = $this->permissionService->hasPermission('category', 'view');

        if ($response) {
            return $response;
        }
        $search = request()->query('search');

        $query = Category::query();

        // Agar 'name' parametri mavjud bo'lsa, qidiruv qo'llaniladi
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%');
        }
        $categorys = $query->latest()->paginate(10);
        return BrandResource::collection($categorys);
    }

    // Create a new category
    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('category', 'add');

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
        $category = Category::create([
            'name'      => $request->name,
            'note'      => $request->note ? $request->note : null,
            'store_id'  => $user->store_id,
        ]);
        return response()->json($category, 201);
    }

    // Show a specific category
    public function show($id)
    {
        $response = $this->permissionService->hasPermission('category', 'view');

        if ($response) {
            return $response;
        }

        $category = Category::find($id);
        if(!$category){
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }

        return new BrandResource($category);
    }

    // Update an existing category
    public function update(Request $request, $id)
    {
        $response = $this->permissionService->hasPermission('category', 'edit');

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

        $category = Category::find($id);
        if(!$category){
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
        $category->name = $request->name;
        $category->note = $request->note;
        $category->update();
        return response()->json([
            'message' => "Update success",
            'data' => $category
        ]);
    }

    // Delete a category
    public function destroy($id)
    {
        $response = $this->permissionService->hasPermission('category', 'delete');
        if ($response) {
            return $response;
        }

        try {
            // Ma'lumotni topish
            $category = Category::find($id);

            if(!$category){
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }
            // O'chirish
            $category->delete();

            return response()->json([
                'message' => 'category deleted successfully'
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return response()->json([
                'error' => 'Cannot delete category. It is being used in other records.',
                'details' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred while deleting the category',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
