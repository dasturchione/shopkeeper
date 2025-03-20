<?php

use App\Http\Controllers\ActionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventorySnapshotController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SoldController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;

Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::get('/admin/exports/products/xls', [ProductController::class, "exportXlsx"]);
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/admin/brands', [BrandController::class, 'index']);
    Route::get('/admin/brands/all', [BrandController::class, 'all']);
    Route::get('/admin/brand/{id}', [BrandController::class, 'show']);
    Route::post('/admin/createbrand', [BrandController::class, 'store']);
    Route::post('/admin/updatebrand/{id}', [BrandController::class, 'update']);
    Route::post('/admin/deletebrand/{id}', [BrandController::class, 'destroy']);

    Route::get('/admin/categorys', [CategoryController::class, 'index']);
    Route::get('/admin/categorys/all', [CategoryController::class, 'all']);
    Route::get('/admin/category/{id}', [CategoryController::class, 'show']);
    Route::post('/admin/createcategory', [CategoryController::class, 'store']);
    Route::post('/admin/updatecategory/{id}', [CategoryController::class, 'update']);
    Route::post('/admin/deletecategory/{id}', [CategoryController::class, 'destroy']);

    Route::get('/admin/clients', [ClientController::class, 'index']);
    Route::get('/admin/clients/all', [ClientController::class, 'all']);
    Route::get('/admin/client/{id}', [ClientController::class, 'show']);
    Route::post('/admin/createclient', [ClientController::class, 'store']);
    Route::post('/admin/updateclient/{id}', [ClientController::class, 'update']);
    Route::post('/admin/deleteclient/{id}', [ClientController::class, 'destroy']);

    Route::get('/admin/suppliers', [SupplierController::class, 'index']);
    Route::get('/admin/suppliers/all', [SupplierController::class, 'all']);
    Route::get('/admin/supplier/{id}', [SupplierController::class, 'show']);
    Route::post('/admin/createsupplier', [SupplierController::class, 'store']);
    Route::post('/admin/updatesupplier/{id}', [SupplierController::class, 'update']);
    Route::post('/admin/deletesupplier/{id}', [SupplierController::class, 'destroy']);

    Route::get('/admin/products', [ProductController::class, 'index']);
    Route::get('/admin/archive/products', [ProductController::class, 'indexArchive']);
    Route::get('/admin/product/{id}', [ProductController::class, 'show']);
    Route::get('/admin/productbybarcode/{id}', [ProductController::class, 'showBarcode']);
    Route::post('/admin/createproduct', [ProductController::class, 'store']);
    Route::post('/admin/updateproduct/{id}', [ProductController::class, 'update']);
    Route::post('/admin/deleteproduct/{id}', [ProductController::class, 'destroy']);

    Route::post('/admin/sale', [SaleController::class, 'store']);
    Route::post('/admin/editsold/{id}', [SoldController::class, 'edit']);
    Route::post('/admin/addproductsold/{id}', [SoldController::class, 'addProduct']);
    Route::get('/admin/soldlist', [SaleController::class, 'index']);
    Route::get('/admin/solditems/{id}', [SoldController::class, 'soldItems']);
    Route::post('/admin/solditem/discountedit/{id}', [SoldController::class, 'soldItemDiscount']);
    Route::post('/admin/productback', [SoldController::class, 'backProduct']);
    Route::get('/barcode', [ProductController::class, 'addBarcodesToProducts']);

    Route::get('admin/users', [UserController::class, 'index']);
    Route::get('admin/permission/user', [UserController::class, 'permissions']);
    Route::get('admin/users/sessions/{id?}', [UserController::class, 'sessions']);
    Route::get('admin/users/action/{id?}', [ActionController::class, 'getUserActions']);
    Route::get('admin/user/{id?}', [UserController::class, 'show']);
    Route::post('admin/adduser', [UserController::class, 'store']);
    Route::post('admin/edituser/{id}', [UserController::class, 'edit']);

    Route::get('/admin/courses', [CourseController::class, 'index']);
    Route::post('/admin/createcourse', [CourseController::class, 'store']);

    Route::get('/admin/action/product/{id}', [ActionController::class, 'getProductActions']);

    Route::get('/admin/dashboard/byday', [DashboardController::class, 'getOrdersByDay']);
    Route::get('/admin/dashboard/byyear', [DashboardController::class, 'getOrdersByYear']);
    Route::get('/admin/dashboard/widget', [DashboardController::class, 'getWidgetInfo']);
    Route::get('/admin/dashboard/filter/byday', [DashboardController::class, 'getFilterOptions']);
    Route::get('/admin/dashboard/filter/byyear', [DashboardController::class, 'getFilterOptionsYear']);
    Route::get('/admin/dashboard/topsales', [DashboardController::class, 'exportTopSoldProducts']);

    Route::get('/admin/inventorysnapshots', [InventorySnapshotController::class, 'index']);
    Route::get('/admin/inventorysnapshot/items/{id}', [InventorySnapshotController::class, 'indexItems']);
    Route::post('/admin/inventorysnapshot/create', [InventorySnapshotController::class, 'store']);
    Route::post('/admin/inventorysnapshot/addtogroup/{id}', [InventorySnapshotController::class, 'addItem']);
    Route::get('/admin/stockbybarcode/{id}', [InventorySnapshotController::class, 'showBarcode']);
    Route::post('/admin/inventorysnapshot/complate/{id}', [InventorySnapshotController::class, 'completeInventoryGroup']);
    Route::get('/admin/exports/remaining-stock/{id}/xls', [InventorySnapshotController::class, 'exportXlsx']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
