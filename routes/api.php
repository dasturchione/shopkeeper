<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\SaleController;

Route::post('/admin/login', [AuthController::class, 'loginAdmin']);
Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/aianal', [AiController::class, 'aisale']);

    Route::get('/admin/brands', [BrandController::class, 'index']);
    Route::get('/admin/brand/{id}', [BrandController::class, 'show']);
    Route::post('/admin/createbrand', [BrandController::class, 'store']);
    Route::post('/admin/updatebrand/{id}', [BrandController::class, 'update']);
    Route::post('/admin/deletebrand/{id}', [BrandController::class, 'destroy']);

    Route::get('/admin/categorys', [CategoryController::class, 'index']);
    Route::get('/admin/category/{id}', [CategoryController::class, 'show']);
    Route::post('/admin/createcategory', [CategoryController::class, 'store']);
    Route::post('/admin/updatecategory/{id}', [CategoryController::class, 'update']);
    Route::post('/admin/deletecategory/{id}', [CategoryController::class, 'destroy']);

    Route::get('/admin/products', [ProductController::class, 'index']);
    Route::get('/admin/product/{id}', [ProductController::class, 'show']);
    Route::post('/admin/createproduct', [ProductController::class, 'store']);
    Route::post('/admin/updateproduct/{id}', [ProductController::class, 'update']);
    Route::post('/admin/deleteproduct/{id}', [ProductController::class, 'destroy']);

    Route::post('/admin/sale', [SaleController::class, 'store']);
    Route::post('/admin/editsale/{id}', [SaleController::class, 'edit']);

    Route::post('/logout', [AuthController::class, 'logout']);
});
