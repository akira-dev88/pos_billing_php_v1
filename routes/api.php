<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API working']);
});

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔐 Protected route (test)
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    // 📦 Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products', [ProductController::class, 'index']);
    
    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/{sale_uuid}', [SaleController::class, 'show']);

    Route::post('/purchases', [PurchaseController::class, 'store']);

});
