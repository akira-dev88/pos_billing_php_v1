<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\CartController;

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

    // 🔍 Search
    Route::get('/products/search', [ProductController::class, 'search']);

    // 📦 Barcode
    Route::get('/products/scan/{barcode}', [ProductController::class, 'findByBarcode']);

    // 🏷 SKU
    Route::get('/products/sku/{sku}', [ProductController::class, 'findBySku']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/{sale_uuid}', [SaleController::class, 'show']);

    Route::post('/carts/{cart_uuid}/checkout', [SaleController::class, 'checkout']);
    
    Route::post('/purchases', [PurchaseController::class, 'store']);
    
    Route::prefix('carts')->group(function () {
        
        
        Route::post('/', [CartController::class, 'create']);
        Route::get('/held', [CartController::class, 'heldCarts']);

        Route::get('/{cart_uuid}', [CartController::class, 'show']);
        Route::post('/{cart_uuid}/items', [CartController::class, 'addItem']);

        Route::post('/{cart_uuid}/hold', [CartController::class, 'hold']);
        Route::post('/{cart_uuid}/resume', [CartController::class, 'resume']);
    });
});
