<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\DispositionController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductModelController;
use Illuminate\Support\Facades\Route;

Route::get('ping', fn () => response()->json(['message' => 'pong']));

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']);
    Route::get('{id}', [CategoryController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [CategoryController::class, 'store']);
        Route::put('{id}', [CategoryController::class, 'update']);
        Route::delete('{id}', [CategoryController::class, 'destroy']);
    });
});

Route::prefix('colors')->group(function () {
    Route::get('/', [ColorController::class, 'index']);
    Route::get('{id}', [ColorController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ColorController::class, 'store']);
        Route::put('{id}', [ColorController::class, 'update']);
        Route::delete('{id}', [ColorController::class, 'destroy']);
    });
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('{id}', [ProductController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ProductController::class, 'store']);
        Route::put('{id}', [ProductController::class, 'update']);
        Route::delete('{id}', [ProductController::class, 'destroy']);
    });
});

Route::prefix('product-models')->group(function () {
    Route::get('/', [ProductModelController::class, 'index']);
    Route::get('{id}', [ProductModelController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ProductModelController::class, 'store']);
        Route::put('{id}', [ProductModelController::class, 'update']);
        Route::delete('{id}', [ProductModelController::class, 'destroy']);
    });
});

Route::prefix('dispositions')->group(function () {
    Route::get('/', [DispositionController::class, 'index']);
    Route::get('{id}', [DispositionController::class, 'show']);

    Route::middleware('auth:api')->group(function () {
        Route::post('/', [DispositionController::class, 'store']);
        Route::put('{id}', [DispositionController::class, 'update']);
        Route::delete('{id}', [DispositionController::class, 'destroy']);
    });
});

Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::get('{id}', [OrderController::class, 'show']);
    Route::post('/', [OrderController::class, 'store']);

    Route::middleware('auth:api')->group(function () {
        Route::put('{id}', [OrderController::class, 'update']);
        Route::delete('{id}', [OrderController::class, 'destroy']);
    });
});
