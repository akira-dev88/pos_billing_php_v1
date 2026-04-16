<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\CustomerPaymentController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SupplierController;

use App\Http\Controllers\Api\ReportController;

use App\Http\Controllers\Api\StaffController;

Route::get('/ping', function () {
    return response()->json(['message' => 'API working']);
});

// Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// 🔐 Protected route (test)
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {

    Route::get('/me', function (Request $request) {

        $user = $request->user();

        $tenant = \App\Models\Tenant::where('tenant_uuid', $user->tenant_uuid)->first();

        return \App\Helpers\ResponseHelper::success([
            'user' => $user,
            'tenant' => [
                'plan' => $tenant->plan,
                'price' => $tenant->price,
                'is_active' => $tenant->is_active,
                'expiry_date' => $tenant->expiry_date,
            ]
        ]);
    });

    Route::get('/products', [ProductController::class, 'index']);

    // 🔍 Search
    Route::get('/products/search', [ProductController::class, 'search']);

    // 📦 Barcode
    Route::get('/products/scan/{barcode}', [ProductController::class, 'findByBarcode']);

    // 🏷 SKU
    Route::get('/products/sku/{sku}', [ProductController::class, 'findBySku']);

    Route::post('/sales', [SaleController::class, 'store']);
    Route::get('/sales/{sale_uuid}', [SaleController::class, 'show']);

    Route::post('/purchases', [PurchaseController::class, 'store']);

    Route::prefix('carts')->group(function () {


        Route::post('/', [CartController::class, 'create']);
        Route::get('/held', [CartController::class, 'heldCarts']);

        Route::get('/{cart_uuid}', [CartController::class, 'show']);
        Route::post('/{cart_uuid}/items', [CartController::class, 'addItem']);

        Route::post('/{cart_uuid}/hold', [CartController::class, 'hold']);
        Route::post('/{cart_uuid}/resume', [CartController::class, 'resume']);
    });

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);

    Route::get('/customers/{customer_uuid}/ledger', [CustomerController::class, 'ledger']);
    Route::post('/customers/{customer_uuid}/payments', [CustomerPaymentController::class, 'store']);

    Route::put('/customers/{customer_uuid}', [CustomerController::class, 'update']);
    Route::delete('/customers/{customer_uuid}', [CustomerController::class, 'destroy']);

    Route::put('/carts/{cart_uuid}/items/{product_uuid}', [CartController::class, 'updateItem']);
    Route::post('/carts/{cart_uuid}/discount', [CartController::class, 'applyDiscount']);

    Route::delete('/carts/{cart_uuid}/items/{product_uuid}', [CartController::class, 'removeItem']);

    Route::get('/sales/{sale_uuid}/invoice', [SaleController::class, 'invoice']);

    Route::get('/sales', [SaleController::class, 'index']);

    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/top-products', [ReportController::class, 'topProducts']);
    Route::get('/reports/stock', [ReportController::class, 'stock']);
    Route::get('/reports/profit', [ReportController::class, 'profit']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/products', [ProductController::class, 'index']);

        Route::get('/products/search', [ProductController::class, 'search']);

        Route::post('/products', [ProductController::class, 'store'])
            ->middleware('role:owner,manager');

        Route::put('/products/{uuid}', [ProductController::class, 'update'])
            ->middleware('role:owner,manager');

        Route::delete('/products/{uuid}', [ProductController::class, 'destroy'])
            ->middleware('role:owner');
    });

    Route::post('/carts/{cart_uuid}/checkout', [SaleController::class, 'checkout'])
        ->middleware('role:owner,manager,cashier');

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/settings', [SettingController::class, 'get']);

        Route::post('/settings', [SettingController::class, 'save'])
            ->middleware('role:owner');

        Route::put('/settings', [SettingController::class, 'update'])
            ->middleware('role:owner');
    });

    Route::middleware(['role:owner'])->group(function () {
        Route::get('/staff', [StaffController::class, 'index']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::put('/staff/{user_uuid}', [StaffController::class, 'update']);
        Route::delete('/staff/{user_uuid}', [StaffController::class, 'destroy']);
    });

    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{supplier_uuid}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{supplier_uuid}', [SupplierController::class, 'destroy']);

    Route::get('/purchases', [PurchaseController::class, 'index']);

    Route::get('/reports/sales-trend', [ReportController::class, 'salesTrend']);

    Route::get('/reports/profit-trend', [ReportController::class, 'profitTrend']);

    Route::get('/customers/summary', [CustomerController::class, 'summary']);

    Route::get('/customers/reminders', [CustomerController::class, 'summary']);

    Route::get('/customers/aging', [CustomerController::class, 'summary']);
});
