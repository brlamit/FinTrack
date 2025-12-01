<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminGroupController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OtpController;
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
Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('auth.reset-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password.post');

// OTP routes
Route::get('/verify-otp', [OtpController::class, 'show'])->name('auth.otp.show');
Route::post('/verify-otp', [OtpController::class, 'verify'])->name('auth.otp.verify');
Route::post('/verify-otp/resend', [OtpController::class, 'resend'])->name('auth.otp.resend');

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
    // Admin profile routes
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile');
    Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
    Route::post('/profile/avatar/remove', [ProfileController::class, 'removeAvatar'])->name('avatar.remove');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');
    Route::post('/impersonate/{user}', [AdminController::class, 'impersonate'])->name('impersonate');

    // Groups
    Route::post('groups/send-engagement-reminders', [AdminGroupController::class, 'sendEngagementReminders'])
        ->name('groups.send-engagement-reminders');
    Route::post('engagement/send-personal-reminders', [AdminGroupController::class, 'sendPersonalEngagementReminders'])
        ->name('engagement.send-personal-reminders');
    Route::resource('groups', AdminGroupController::class);
    Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\GroupMemberController::class, 'remove'])->name('groups.member.remove');
});

// Web routes for authenticated users
Route::middleware(['auth'])->group(function () {
    // User profile and settings
    Route::get('/dashboard', [UserController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/profile', [UserController::class, 'profile'])->name('user.profile');
    Route::get('/profile/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar'])->name('user.avatar.update');
    Route::put('/profile', [UserController::class, 'update'])->name('user.update');
    Route::get('/security', [UserController::class, 'security'])->name('user.security');
    Route::put('/password', [UserController::class, 'updatePassword'])->name('user.update-password');
    Route::post('/password/send-otp', [OtpController::class, 'sendPasswordChangeOtp'])->name('user.password.send-otp');
    Route::get('/preferences', [UserController::class, 'preferences'])->name('user.preferences');
    Route::put('/preferences', [UserController::class, 'updatePreferences'])->name('user.update-preferences');
    Route::post('/logout-all', [UserController::class, 'logoutAll'])->name('user.logout-all');
    Route::post('/2fa/disable', [UserController::class, 'disable2FA'])->name('user.disable-2fa');
    Route::get('/2fa/enable', [UserController::class, 'enable2FA'])->name('user.enable-2fa');

    // User transactions
    Route::get('/transactions', [UserController::class, 'transactions'])->name('user.transactions');
    Route::get('/transactions/create', [UserController::class, 'createTransaction'])->name('user.transactions.create');
    // Web view for single transaction
    Route::get('/transactions/{transaction}/view', [\App\Http\Controllers\Web\TransactionController::class, 'show'])->name('user.transaction.show');
    Route::post('/transactions', [UserController::class, 'storeTransaction'])->name('user.transactions.store');

    // User budgets
    Route::get('/budgets', [UserController::class, 'budgets'])->name('user.budgets');

    // User groups
    Route::get('/groups', [UserController::class, 'groups'])->name('user.groups');
    Route::get('/groups/{group}', [UserController::class, 'group'])->name('user.group');
    Route::get('/groups/{group}/transactions', [UserController::class, 'groupTransactions'])->name('user.group.transactions');
    Route::post('/groups', [\App\Http\Controllers\GroupController::class, 'store'])->name('user.groups.store');
    Route::delete('/groups/{group}', [\App\Http\Controllers\GroupController::class, 'destroy'])->name('user.groups.destroy');
    // Add expense (split) from web form
    Route::post('groups/{group}/split', [\App\Http\Controllers\GroupController::class, 'splitExpense'])->name('groups.split');

    // User reports
    Route::get('/reports', [UserController::class, 'reports'])->name('user.reports');

    // Notifications (web view)
    Route::get('/notifications', [UserController::class, 'notifications'])->name('user.notifications');
    Route::post('/notifications/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('user.notifications.mark-all-read');
    Route::post('/notifications/{notification}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('user.notifications.mark-read');

    // Group member invite
    Route::post('groups/{group}/invite', [\App\Http\Controllers\GroupMemberController::class, 'invite'])->name('groups.invite');
    Route::delete('groups/{group}/members/{member}', [\App\Http\Controllers\GroupMemberController::class, 'remove'])->name('groups.member.remove');
});

// -----------------------------------------
// Local diagnostic routes (only enabled in local environment)
// Use to verify Storage disk uploads to Supabase buckets.
// Accessible at: /_diagnose/supabase-upload when APP_ENV=local
// -----------------------------------------
if (app()->environment('local')) {
    Route::get('/_diagnose/supabase-upload', function () {
        try {
            $disk = env('AVATAR_DISK', 'supabase_avatars');
            $path = 'diagnostics/' . uniqid('diag_', true) . '.txt';
            \Illuminate\Support\Facades\Storage::disk($disk)->put($path, 'ok');
            $exists = \Illuminate\Support\Facades\Storage::disk($disk)->exists($path);

            return response()->json([
                'ok' => true,
                'disk' => $disk,
                'path' => $path,
                'exists' => $exists,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    });
}
