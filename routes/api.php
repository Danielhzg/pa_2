<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

// API Routes
Route::delete('/users/truncate', [AuthController::class, 'truncateUsers'])->middleware('auth:api');