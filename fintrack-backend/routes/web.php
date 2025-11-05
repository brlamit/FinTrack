<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $user = auth()->user();
    
    if ($user && $user->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    
    if ($user) {
        return redirect()->route('user.dashboard');
    }
    
    return view('welcome');
});

Route::get('/docs', function () {
    return view('scramble');
});

Route::get('/docs/api', function () {
    return response()->json(app(\Dedoc\Scramble\Scramble::class)->generate());
});

// ============================================
// USER AUTHENTICATION ROUTES
// ============================================

// Login routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login.post');

// Register routes
Route::get('/register', [AuthController::class, 'showRegister'])->name('auth.register');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register.post');

// Forgot password routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('auth.forgot-password');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('auth.send-reset-link');

// Reset password routes
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('auth.reset-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password.post');

// Logout route (requires auth)
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout')->middleware('auth');

// First login - force password change
Route::get('/force-password-change', [AuthController::class, 'showForcePasswordChange'])
    ->name('auth.force-password-change')
    ->middleware('auth');
Route::post('/force-password-change', [AuthController::class, 'forcePasswordChange'])
    ->name('auth.force-password-change.post')
    ->middleware('auth');

// ============================================
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
    Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\GroupMemberController::class, 'remove'])->name('groups.member.remove');
});

// Web routes for authenticated users
Route::middleware(['auth'])->group(function () {
    // User profile and settings
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/profile/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/profile', [UserController::class, 'update'])->name('user.update');
    Route::get('/security', [UserController::class, 'security'])->name('user.security');
    Route::put('/password', [UserController::class, 'updatePassword'])->name('user.update-password');
    Route::get('/preferences', [UserController::class, 'preferences'])->name('user.preferences');
    Route::put('/preferences', [UserController::class, 'updatePreferences'])->name('user.update-preferences');
    Route::post('/logout-all', [UserController::class, 'logoutAll'])->name('user.logout-all');
    Route::post('/2fa/disable', [UserController::class, 'disable2FA'])->name('user.disable-2fa');
    Route::get('/2fa/enable', [UserController::class, 'enable2FA'])->name('user.enable-2fa');

    // User transactions
    Route::get('/transactions', [UserController::class, 'transactions'])->name('user.transactions');
    Route::get('/transactions/create', [UserController::class, 'createTransaction'])->name('user.transactions.create');
    Route::post('/transactions', [UserController::class, 'storeTransaction'])->name('user.transactions.store');

    // User budgets
    Route::get('/budgets', [UserController::class, 'budgets'])->name('user.budgets');

    // User groups
    Route::get('/groups', [UserController::class, 'groups'])->name('user.groups');
    Route::get('/groups/{group}', [UserController::class, 'group'])->name('user.group');
    // Add expense (split) from web form
    Route::post('groups/{group}/split', [\App\Http\Controllers\GroupController::class, 'splitExpense'])->name('groups.split');

    // User reports
    Route::get('/reports', [UserController::class, 'reports'])->name('user.reports');

    // Group member invite
    Route::post('groups/{group}/invite', [\App\Http\Controllers\GroupMemberController::class, 'invite'])->name('groups.invite');
    Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\GroupMemberController::class, 'remove'])->name('groups.member.remove');
});
