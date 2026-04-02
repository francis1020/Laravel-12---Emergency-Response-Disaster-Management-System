<?php

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\EmergencyReportController;
use App\Http\Controllers\ResponderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportHistoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReportCommentController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MultiResponderController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;


Route::get('/', [EmergencyReportController::class, 'index'])->name('reports.map');

// Route::get('/', function () {
//     return view('reports.map');
// })->middleware(['auth', 'verified']);

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');


Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/validate-password', [ProfileController::class, 'validatePassword'])->name('profile.validate-password');
    Route::post('/profile/responder-settings', [ProfileController::class, 'updateResponderSettings'])->name('profile.responder-settings');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Reports
    Route::post('/', [EmergencyReportController::class, 'store'])->name('report.store');
    Route::get('/reports/history', [ReportHistoryController::class, 'index'])->name('reports.history');
    Route::get('/reports/{report_id}', [ReportHistoryController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report_id}', [ReportHistoryController::class, 'update'])->name('reports.update');
    Route::post('/reports/{report_id}/cancel', [ReportHistoryController::class, 'cancel'])->name('reports.cancel');
    Route::delete('/reports/{report_id}', [ReportHistoryController::class, 'destroy'])->name('reports.destroy');

    // Export
    Route::get('/reports/export/csv', [ExportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/export/pdf', [ExportController::class, 'exportPdf'])->name('reports.export.pdf');

    // Comments
    Route::post('/reports/{report_id}/comments', [ReportCommentController::class, 'store'])->name('reports.comments.store');
    Route::get('/reports/{report_id}/comments', [ReportCommentController::class, 'getComments'])->name('reports.comments.get');

    // Media/File Uploads
    Route::post('/reports/{reportId}/media', [MediaController::class, 'store'])->name('reports.media.store');
    Route::get('/reports/{reportId}/media', [MediaController::class, 'index'])->name('reports.media.index');
    Route::delete('/media/{mediaId}', [MediaController::class, 'destroy'])->name('media.destroy');

    // Multi-Responder Assignment
    Route::post('/reports/{reportId}/responders/assign', [MultiResponderController::class, 'assign'])->name('reports.responders.assign');
    Route::get('/reports/{reportId}/responders', [MultiResponderController::class, 'index'])->name('reports.responders.index');
    Route::patch('/responder-assignments/{assignmentId}/status', [MultiResponderController::class, 'updateStatus'])->name('responder-assignments.update-status');
    Route::delete('/responder-assignments/{assignmentId}', [MultiResponderController::class, 'remove'])->name('responder-assignments.remove');

    // Messages/Chat
    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    // Redirect old messages route to new messages page
    Route::get('/reports/{reportId}/messages', function($reportId) {
        return redirect()->route('messages.index', ['report_id' => $reportId]);
    })->name('reports.messages.redirect');
    Route::post('/reports/{reportId}/messages', [MessageController::class, 'store'])->name('reports.messages.store');
    Route::get('/reports/{reportId}/messages/api', [MessageController::class, 'getMessages'])->name('reports.messages.api');
    Route::patch('/messages/{messageId}/read', [MessageController::class, 'markAsRead'])->name('messages.mark-read');
    Route::get('/messages/unread-count', [MessageController::class, 'unreadCount'])->name('messages.unread-count');
    Route::get('/messages/recent', [MessageController::class, 'recentMessages'])->name('messages.recent');
    Route::get('/messages/accessible-reports', [MessageController::class, 'getAccessibleReportIds'])->name('messages.accessible-reports');

    // Notifications
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::get('/notifications/unread', [NotificationController::class, 'unreadNotifications'])->name('notifications.unread');
    Route::patch('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');

    // Resources - Specific routes must come before parameterized routes
    Route::get('/resources', [ResourceController::class, 'index'])->name('resources.index');
    Route::get('/resources/available', [ResourceController::class, 'getAvailable'])->name('resources.available');
    Route::get('/resources/{id}', [ResourceController::class, 'show'])->name('resources.show');
    Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::patch('/resources/{id}', [ResourceController::class, 'update'])->name('resources.update');
    Route::post('/resources/{resourceId}/assign', [ResourceController::class, 'assignToReport'])->name('resources.assign');
    Route::post('/resources/{resourceId}/release', [ResourceController::class, 'release'])->name('resources.release');
    Route::delete('/resources/{id}', [ResourceController::class, 'destroy'])->name('resources.destroy');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Responder routes
    Route::post('/send-location', [ResponderController::class, 'send']);

    Route::middleware(['auth', 'user.type:1'])->group(function () {
        Route::get('/response/{id}', [ResponderController::class, 'show'])
            ->name('response.show');
    });

    Route::post('/responder/respond', [ResponderController::class, 'respond'])->name('responder.respond');

    // Admin routes
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        // Users
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
        Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
        Route::get('/users/{id}/edit', [AdminController::class, 'editUser'])->name('users.edit');
        Route::patch('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');

        // Emergency Types
        Route::get('/emergency-types', [AdminController::class, 'emergencyTypes'])->name('emergency-types');
        Route::get('/emergency-types/create', [AdminController::class, 'createEmergencyType'])->name('emergency-types.create');
        Route::post('/emergency-types', [AdminController::class, 'storeEmergencyType'])->name('emergency-types.store');
        Route::get('/emergency-types/{id}/edit', [AdminController::class, 'editEmergencyType'])->name('emergency-types.edit');
        Route::patch('/emergency-types/{id}', [AdminController::class, 'updateEmergencyType'])->name('emergency-types.update');
        Route::delete('/emergency-types/{id}', [AdminController::class, 'deleteEmergencyType'])->name('emergency-types.delete');

        // Reports
        Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    });
});

require __DIR__.'/auth.php';
