<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Apply API validation middleware to all API routes
Route::middleware(['api.validation'])->group(function () {

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:sanctum');
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('password', [AuthController::class, 'updatePassword']);

    // Transactions
    Route::get('transactions/statistics', [\App\Http\Controllers\TransactionController::class, 'statistics']);
    Route::apiResource('transactions', \App\Http\Controllers\TransactionController::class);

    // Categories
    Route::apiResource('categories', \App\Http\Controllers\CategoryController::class);

    // Receipts
    Route::post('receipts/presign', [\App\Http\Controllers\ReceiptController::class, 'presign']);
    Route::post('receipts/complete', [\App\Http\Controllers\ReceiptController::class, 'complete']);
    Route::get('receipts/{receipt}/download', [\App\Http\Controllers\ReceiptController::class, 'download']);
    Route::apiResource('receipts', \App\Http\Controllers\ReceiptController::class);

    // Groups/Family
    Route::apiResource('groups', \App\Http\Controllers\GroupController::class);
    Route::post('groups/{group}/invite', [\App\Http\Controllers\GroupMemberController::class, 'invite']);
    Route::get('groups/{group}/members', [\App\Http\Controllers\GroupController::class, 'members']);
    Route::post('groups/{group}/split', [\App\Http\Controllers\GroupController::class, 'splitExpense']);

    // Budgets
    Route::apiResource('budgets', \App\Http\Controllers\BudgetController::class);

    // Reports
    Route::get('reports/spending', [\App\Http\Controllers\ReportController::class, 'spending']);
    Route::get('reports/{report}/export', [\App\Http\Controllers\ReportController::class, 'export']);

    // Insights
    Route::get('insights', [\App\Http\Controllers\InsightController::class, 'index']);

    // Notifications
    Route::get('notifications', [\App\Http\Controllers\NotificationController::class, 'index']);

    // Sync
    Route::post('sync/transactions', [\App\Http\Controllers\SyncController::class, 'transactions']);

    // Voice & QR
    Route::post('voice/parse', [\App\Http\Controllers\VoiceController::class, 'parse']);
    Route::post('qr/parse', [\App\Http\Controllers\QrController::class, 'parse']);
});

// Admin routes (requires admin role)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('users', [\App\Http\Controllers\AdminController::class, 'users']);
    Route::get('transactions', [\App\Http\Controllers\AdminController::class, 'transactions']);
    Route::post('impersonate', [\App\Http\Controllers\AdminController::class, 'impersonate']);
});

});