<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminGroupController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/docs', function () {
    return view('scramble');
});

Route::get('/docs/api', function () {
    return response()->json(app(\Dedoc\Scramble\Scramble::class)->generate());
});

// Admin auth
Route::get('/admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::post('/impersonate/{user}', [AdminController::class, 'impersonate'])->name('impersonate');

    // Groups
    Route::resource('groups', AdminGroupController::class);
});

// Web routes for authenticated users
Route::middleware(['auth'])->group(function () {
    Route::post('groups/{group}/invite', [\App\Http\Controllers\GroupMemberController::class, 'invite'])->name('groups.invite');
});
