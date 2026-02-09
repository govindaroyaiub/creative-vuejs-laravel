<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CacheManagementController;
use App\Http\Controllers\NotificationController;

// Timezone detection route
Route::post('/set-timezone', function (Illuminate\Http\Request $request) {
    $timezone = $request->input('timezone');

    if ($timezone && in_array($timezone, timezone_identifiers_list())) {
        session(['user_timezone' => $timezone]);

        return response()->json([
            'success' => true,
            'timezone' => $timezone,
            'message' => 'Timezone set successfully'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Invalid timezone'
    ], 400);
});

// Cache Management API Routes (JSON responses)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/cache-management/stats', [CacheManagementController::class, 'getStats']);
    Route::get('/cache-management/server-time', [CacheManagementController::class, 'getServerTime']);
    Route::get('/cache-management/system-info', [CacheManagementController::class, 'getSystemInfoOnly']);
    Route::get('/cache-management/recent-cleanups', [CacheManagementController::class, 'getRecentCleanupsApi']);

    // Notification API Routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/{id}/mark-as-read', [NotificationController::class, 'markAsRead']);
        Route::post('/{id}/mark-as-unread', [NotificationController::class, 'markAsUnread']);
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
        Route::delete('/all/read', [NotificationController::class, 'deleteAllRead']);
        Route::delete('/all', [NotificationController::class, 'deleteAll']);
    });
});
