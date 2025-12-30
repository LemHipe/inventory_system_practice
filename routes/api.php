<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\DispatchController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\UserController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::post('/upload-csv', [InventoryController::class, 'uploadCsv']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::put('/{id}', [InventoryController::class, 'update']);
        Route::delete('/{id}', [InventoryController::class, 'destroy']);
        Route::post('/{id}/add-stock', [InventoryController::class, 'addStock']);
        Route::post('/{id}/remove-stock', [InventoryController::class, 'removeStock']);
        Route::get('/{id}/price-history', [InventoryController::class, 'priceHistory']);
    });

    Route::prefix('chats')->group(function () {
        Route::get('/', [ChatController::class, 'index']);
        Route::post('/', [ChatController::class, 'store']);
        Route::get('/{chatId}/messages', [ChatController::class, 'messages']);
        Route::post('/{chatId}/messages', [ChatController::class, 'sendMessage']);
    });

    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::post('/', [WarehouseController::class, 'store']);
        Route::get('/{id}', [WarehouseController::class, 'show']);
        Route::put('/{id}', [WarehouseController::class, 'update']);
        Route::delete('/{id}', [WarehouseController::class, 'destroy']);
    });

    Route::prefix('dispatches')->group(function () {
        Route::get('/', [DispatchController::class, 'index']);
        Route::post('/', [DispatchController::class, 'store']);
        Route::get('/{id}', [DispatchController::class, 'show']);
        Route::put('/{id}', [DispatchController::class, 'update']);
        Route::delete('/{id}', [DispatchController::class, 'destroy']);
    });

    Route::prefix('activity-logs')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index']);
        Route::get('/{id}', [ActivityLogController::class, 'show']);
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
    });
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok']);
});
