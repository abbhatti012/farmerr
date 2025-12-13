<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AdminApiAuthController;
use App\Http\Controllers\Api\AdminOrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ✅ Admin API routes
Route::prefix('admin')->group(function () {
    Route::post('login', [AdminApiAuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AdminApiAuthController::class, 'logout']);
        Route::get('me', [AdminApiAuthController::class, 'me']);
    });
});


Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('orders', [AdminOrderController::class, 'index']);
    Route::get('orders/{order}', [AdminOrderController::class, 'show']);

});