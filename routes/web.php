<?php

use App\Http\Controllers\Web\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Web\Admin\PropertyController as AdminPropertyController;
use App\Http\Controllers\Web\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Web\Admin\UserController as AdminUserController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\TicketController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
	Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
	Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
	Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

	Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

	Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
	Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
	Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
	Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
	Route::post('/tickets/{ticket}/review', [TicketController::class, 'review'])->name('tickets.review');
	Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
	Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
	Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

	Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
	Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

	Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
		Route::resource('properties', AdminPropertyController::class)->except(['show']);
		Route::resource('categories', AdminCategoryController::class)->except(['show']);
		Route::resource('users', AdminUserController::class)->except(['show']);
		Route::get('settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
		Route::put('settings', [AdminSettingsController::class, 'update'])->name('settings.update');
		Route::post('settings/test-smtp', [AdminSettingsController::class, 'testSmtp'])->name('settings.test-smtp');
		Route::post('settings/test-sms', [AdminSettingsController::class, 'testSms'])->name('settings.test-sms');
		Route::post('settings/reset-data', [AdminSettingsController::class, 'resetData'])->name('settings.reset-data');
	});
});
