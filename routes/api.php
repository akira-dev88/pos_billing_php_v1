<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;

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
});
