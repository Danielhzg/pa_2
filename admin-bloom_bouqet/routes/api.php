<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\CarouselController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Auth routes
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
    });

    // Product endpoints
    Route::get('products', [ProductController::class, 'index']); // Get all products
    Route::get('products/category/{category}', [ProductController::class, 'getByCategory']); // Get products by category
    Route::post('products', [ProductController::class, 'store']); // Add product
    
    // Category endpoints
    Route::get('categories', [CategoryController::class, 'index']); // Get all categories

    // Carousel endpoint
    Route::get('carousels', [CarouselController::class, 'index']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
    });
});

Route::get('/products', [ProductController::class, 'index']);