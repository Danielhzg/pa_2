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
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('otp.verify');
    Route::post('resend-otp', [AuthController::class, 'resendOtp'])->name('otp.resend');
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('update-profile', [AuthController::class, 'updateProfile']);
    });

    // Product endpoints
    Route::get('products', [ProductController::class, 'index']); // Get all products
    Route::get('products/search', [ProductController::class, 'search']); // Search products by query
    Route::get('products/category/{category}', [ProductController::class, 'getByCategory']); // Get products by category
    Route::post('products', [ProductController::class, 'store']); // Add product
    
    // Category endpoints
    Route::get('categories', [CategoryController::class, 'index']); // Get all categories

    // Carousel endpoint
    Route::get('carousels', [CarouselController::class, 'index']);

    // Fetch user by email
    Route::get('user/{email}', [AuthController::class, 'getUserByEmail'])->name('user.get');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});