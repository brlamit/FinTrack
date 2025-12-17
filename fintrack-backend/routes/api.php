<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMemberController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\VoiceController;
use App\Http\Controllers\QrController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// ============================================================================
// Current authenticated user
// ============================================================================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


// ============================================================================
// APPLY YOUR API VALIDATION MIDDLEWARE
// ============================================================================
Route::middleware(['api.validation'])->group(function () {


// ============================================================================
// AUTH ROUTES (Public)
// ============================================================================
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Forgot + Reset password
    Route::post('forgot-password/send-link', [AuthController::class, 'sendResetLink']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    // Password update w/ OTP
    Route::put('password', [UserController::class, 'updatePassword']);
    Route::post('password/send-otp', [OtpController::class, 'sendPasswordChangeOtp']);

    // OTP Verify/Resend
    Route::post('otp/verify', [OtpController::class, 'verify']);
    Route::post('otp/resend', [OtpController::class, 'resend']);

    // First login force password change
    Route::post('force-password-change', [AuthController::class, 'forcePasswordChange']);

    // Logout all devices
    Route::post('logout-all', [UserController::class, 'logoutAll']);

    // 2FA
    Route::get('2fa/enable', [UserController::class, 'enable2FA']);
    Route::post('2fa/disable', [UserController::class, 'disable2FA']);
});


// ============================================================================
// PROTECTED ROUTES (Requires login)
// ============================================================================
Route::middleware(['auth:sanctum'])->group(function () {

    // -------------------------------------------------------
    // 📊 DASHBOARD
    // -------------------------------------------------------
    Route::get('dashboard', [UserController::class, 'dashboard']);

    // -------------------------------------------------------
    // 🧑 USER PROFILE
    // -------------------------------------------------------
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::get('me/profile', [UserController::class, 'profile']);

    // Dashboard summary (JSON), mirroring the web dashboard
    Route::get('dashboard', [UserController::class, 'dashboard']);

    // 🔥 Added from web.php (settings & security)
    Route::get('security', [UserController::class, 'security']);
    Route::get('preferences', [UserController::class, 'preferences']);
    Route::put('preferences', [UserController::class, 'updatePreferences']);

    // Avatar
    Route::post('profile/avatar', [UserController::class, 'updateAvatar']);
    Route::post('profile/avatar/remove', [UserController::class, 'removeAvatar']);


    // -------------------------------------------------------
    // 💰 TRANSACTIONS
    // -------------------------------------------------------
    Route::get('transactions/statistics', [TransactionController::class, 'statistics']);
    Route::apiResource('transactions', TransactionController::class);

    // -------------------------------------------------------
    // 🗂️ CATEGORIES
    // -------------------------------------------------------
    Route::apiResource('categories', CategoryController::class);

    // -------------------------------------------------------
    // 🧾 RECEIPTS
    // -------------------------------------------------------
    Route::post('receipts/upload', [ReceiptController::class, 'uploadDirect']);
    Route::post('receipts/presign', [ReceiptController::class, 'presign']);
    Route::post('receipts/complete', [ReceiptController::class, 'complete']);
    Route::get('receipts/{receipt}/download', [ReceiptController::class, 'download']);
    Route::get('receipts/{receipt}/parsed', [ReceiptController::class, 'parsed']);
    Route::apiResource('receipts', ReceiptController::class);

    // -------------------------------------------------------
    // 👥 GROUPS & MEMBERS
    // -------------------------------------------------------
    Route::get('groups', [UserController::class, 'groups']);
    Route::get('groups/{group}', [UserController::class, 'group']);
    Route::get('groups/{group}/members', [GroupController::class, 'members']);
    Route::post('groups', [GroupController::class, 'store']);
    Route::delete('groups/{group}', [GroupController::class, 'destroy']);
    Route::post('groups/{group}/invite', [GroupMemberController::class, 'invite']);
    Route::post('groups/{group}/split', [GroupController::class, 'splitExpense']);
    Route::post('groups/{group}/split-expense-form', [GroupController::class, 'splitExpenseForm']);
    Route::post('groups/{group}/invite-form', [GroupMemberController::class, 'inviteForm']);
    Route::delete('groups/{group}/members/{member}', [GroupMemberController::class, 'removeMember']);

    // 🔥 Added from web.php (group transactions view)
    Route::get('groups/{group}/transactions', [UserController::class, 'groupTransactions']);


    // -------------------------------------------------------
    // 📊 BUDGETS
    // -------------------------------------------------------
    Route::apiResource('budgets', BudgetController::class);

    // Convenience routes
    Route::post('me/budgets', [UserController::class, 'storeBudget']);
    Route::put('me/budgets/{budget}', [UserController::class, 'updateBudget']);


    // -------------------------------------------------------
    // 📈 REPORTS
    // -------------------------------------------------------
    Route::get('reports/report_sheet', [ReportController::class, 'reportsheet']);
    Route::get('reports/spending', [ReportController::class, 'spending']);
    Route::get('reports/{report}/export', [ReportController::class, 'export']);

    // 🔥 Added (for frontend JS)
    Route::get('reports/spending/data', [ReportController::class, 'spending']);


    // -------------------------------------------------------
    // 💡 INSIGHTS
    // -------------------------------------------------------
    Route::get('insights', [InsightController::class, 'index']);


    // -------------------------------------------------------
    // 🔔 NOTIFICATIONS
    // -------------------------------------------------------
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);


    // -------------------------------------------------------
    // 🔄 SYNC
    // -------------------------------------------------------
    Route::post('sync/transactions', [SyncController::class, 'transactions']);

    // -------------------------------------------------------
    // 🎤 VOICE + QR
    // -------------------------------------------------------
    Route::post('voice/parse', [VoiceController::class, 'parse']);
    Route::post('qr/parse', [QrController::class, 'parse']);
});


// ============================================
// ADMIN API ROUTES
// ============================================
Route::prefix('admin')->group(function () {

    // -------------------------------
    // ADMIN LOGIN (Public)
    // -------------------------------
    Route::post('login', [\App\Http\Controllers\Admin\AdminAuthController::class, 'login']);

    // -------------------------------
    // ADMIN PROTECTED ROUTES
    // -------------------------------
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {

        Route::get('users', [AdminController::class, 'users']);
        Route::get('transactions', [AdminController::class, 'transactions']);
        Route::post('impersonate/{user}', [AdminController::class, 'impersonate']);

        // NEW (from your message)
        Route::post('groups/send-engagement-reminders', [\App\Http\Controllers\Admin\AdminGroupController::class, 'sendEngagementReminders']);
        Route::post('engagement/send-personal-reminders', [\App\Http\Controllers\Admin\AdminGroupController::class, 'sendPersonalEngagementReminders']);
    });
});


});
