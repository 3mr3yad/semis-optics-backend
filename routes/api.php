<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('{slug}', [ProductController::class, 'show']);
});

Route::middleware('auth:api')->prefix('dashboard')->group(function () {
    Route::get('products', [ProductController::class, 'dashboardIndex']);
    Route::post('products', [ProductController::class, 'dashboardStore']);
    Route::put('products/{product}', [ProductController::class, 'dashboardUpdate']);
    Route::delete('products/{product}', [ProductController::class, 'dashboardDestroy']);
    Route::post('uploads/product-image', [ProductController::class, 'uploadImage']);
});
