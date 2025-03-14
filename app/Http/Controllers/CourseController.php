<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Store;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{

    protected $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $user = Auth::user();
        $courses = Course::where('store_id', $user->store_id)->latest()->paginate(10);
        return CourseResource::collection($courses);
    }

    public function store(Request $request)
    {
        $response = $this->permissionService->hasPermission('course', 'add');

        if ($response) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'rate' => 'required|numeric',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = Auth::user();
        $store = Store::find($user->store_id);

        DB::beginTransaction();

        try {
            $course = Course::create([
                'rate'       => $request->rate,
                'main_id'    => $store->maincurrency,
                'convert_id' => $store->convertcurrency,
                'store_id'   => $store->id
            ]);

            Store::where('id', $user->store_id)->update([
                'course_id' => $course->id
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Course successfully created!',
                'data'    => $course
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong!',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
