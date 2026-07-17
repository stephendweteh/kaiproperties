<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\Mobile\AppConfigController;
use App\Http\Controllers\Api\Mobile\AuthController as MobileAuthController;
use App\Http\Controllers\Api\Mobile\DashboardController as MobileDashboardController;
use App\Http\Controllers\Api\Mobile\DeviceTokenController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\TicketController as MobileTicketController;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Web / Admin API  –  v1
// ---------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------
// Mobile API  –  mobile/v1
// Base URL: https://portal.kaipropertiesgh.com/api/mobile/v1
// ---------------------------------------------------------------------------
Route::prefix('mobile/v1')->name('mobile.v1.')->group(function (): void {

    // Public ─────────────────────────────────────────────────────────────────
    Route::get('/config', [AppConfigController::class, 'index'])->name('config');
    Route::post('/auth/register', [MobileAuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [MobileAuthController::class, 'login'])->name('auth.login');

    // Protected ───────────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::get('/auth/me', [MobileAuthController::class, 'me'])->name('auth.me');
        Route::post('/auth/logout', [MobileAuthController::class, 'logout'])->name('auth.logout');
        Route::post('/auth/change-password', [MobileAuthController::class, 'changePassword'])->name('auth.change-password');

        // Profile
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])->name('profile.photo');

        // Dashboard
        Route::get('/dashboard', [MobileDashboardController::class, 'index'])->name('dashboard');

        // References (shared)
        Route::get('/references/properties', [ReferenceController::class, 'properties'])->name('references.properties');
        Route::get('/references/categories', [ReferenceController::class, 'categories'])->name('references.categories');
        Route::get('/references/technicians', [ReferenceController::class, 'technicians'])->name('references.technicians');
        Route::get('/references/reporters', [ReferenceController::class, 'reporters'])->name('references.reporters');

        // Tickets
        Route::get('/tickets', [MobileTicketController::class, 'index'])->name('tickets.index');
        Route::post('/tickets', [MobileTicketController::class, 'store'])->name('tickets.store');
        Route::get('/tickets/{ticket}', [MobileTicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [MobileTicketController::class, 'update'])->name('tickets.update');
        Route::patch('/tickets/{ticket}/assign', [MobileTicketController::class, 'assign'])->name('tickets.assign');
        Route::patch('/tickets/{ticket}/status', [MobileTicketController::class, 'changeStatus'])->name('tickets.status');
        Route::post('/tickets/{ticket}/attachments', [MobileTicketController::class, 'uploadAttachment'])->name('tickets.attachments.upload');

        // Ticket Phases
        Route::get('/tickets/{ticket}/phases', [MobileTicketController::class, 'phases'])->name('tickets.phases.index');
        Route::post('/tickets/{ticket}/phases', [MobileTicketController::class, 'addPhase'])->name('tickets.phases.store');
        Route::patch('/tickets/{ticket}/phases/{phase}', [MobileTicketController::class, 'updatePhase'])->name('tickets.phases.update');
        Route::patch('/tickets/{ticket}/phases/{phase}/complete', [MobileTicketController::class, 'completePhase'])->name('tickets.phases.complete');
        Route::post('/tickets/{ticket}/phases/{phase}/attachments', [MobileTicketController::class, 'uploadPhaseAttachment'])->name('tickets.phases.attachments.upload');

        // Cost Requests
        Route::post('/tickets/{ticket}/cost-requests', [MobileTicketController::class, 'submitCostRequest'])->name('tickets.cost-requests.store');
        Route::patch('/cost-requests/{costRequest}/review', [MobileTicketController::class, 'reviewCostRequest'])->name('cost-requests.review');

        // Push Notification Device Tokens
        Route::post('/device-tokens', [DeviceTokenController::class, 'store'])->name('device-tokens.store');
        Route::delete('/device-tokens/{token}', [DeviceTokenController::class, 'destroy'])->name('device-tokens.destroy');
    });
});
