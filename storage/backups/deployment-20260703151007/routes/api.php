<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::get('/references/properties', [ReferenceController::class, 'properties']);
        Route::get('/references/categories', [ReferenceController::class, 'categories']);
        Route::get('/references/technicians', [ReferenceController::class, 'technicians']);

        Route::get('/tickets', [TicketController::class, 'index']);
        Route::post('/tickets', [TicketController::class, 'store']);
        Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
        Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign']);
        Route::patch('/tickets/{ticket}/status', [TicketController::class, 'changeStatus']);
        Route::post('/tickets/{ticket}/cost-requests', [TicketController::class, 'submitCostRequest']);
        Route::patch('/cost-requests/{costRequest}/review', [TicketController::class, 'reviewCostRequest']);

        Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
            Route::apiResource('properties', AdminPropertyController::class);
            Route::apiResource('categories', AdminCategoryController::class);
            Route::apiResource('users', AdminUserController::class);
        });
    });
});
