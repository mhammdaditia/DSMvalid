<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ActivityLogController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::prefix('DSMvalid')->group(function () {
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
        ->group(function () {
        
        // ========================================
        // ? DASHBOARD
        // ========================================
        Route::get('/home-dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // ✅ Clear Dashboard Cache (Admin/Super Admin Only)
        Route::post('/home-dashboard/clear-cache', [DashboardController::class, 'clearCache'])
            ->middleware('admin')
            ->name('dashboard.clear-cache');
        
        // Redirect old dashboard URL
        Route::get('/dashboard', function () {
            return redirect('/DSMvalid/home-dashboard');
        });
        
        // ========================================
        // ? EVENTS MANAGEMENT
        // ========================================
        Route::get('/event-management', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/check-video/{id}', [EventController::class, 'checkVideo'])->name('events.check-video');
        Route::post('/event-management/{id}/submit-validation', [EventController::class, 'storeValidation'])->name('events.store-validation');
        
        // ========================================
        // ? VALIDATIONS HISTORY
        // ========================================
        Route::get('/validations', [ValidationController::class, 'index'])->name('validations.index');
        
        // ✅ VIEW FILE ROUTE - URL Obfuscated (Public untuk Validator)
        Route::get('/file/view/{token}', [ValidationController::class, 'viewFile'])->name('file.view');
        
        // Admin-only validation routes
        Route::middleware('admin')->group(function () {
            Route::post('/validations/{id}', [ValidationController::class, 'update'])->name('validations.update'); // POST untuk file upload
            Route::delete('/validations/{id}', [ValidationController::class, 'destroy'])->name('validations.destroy');
            Route::post('/validations/bulk-delete', [ValidationController::class, 'bulkDelete'])->name('validations.bulk-delete');
        });

        // ========================================
        // ? USER MANAGEMENT (Admin Only)
        // ========================================
        Route::middleware('admin')->group(function () {
            Route::get('/user-management', [UserController::class, 'index'])->name('users.index');
            Route::post('/user-management/create-new', [UserController::class, 'store'])->name('users.store');
            Route::post('/user-management/{id}/update', [UserController::class, 'update'])->name('users.update');
            Route::delete('/user-management/{id}/delete', [UserController::class, 'destroy'])->name('users.destroy');
        });

        // ========================================
        // ? VIDEO STREAMING
        // ========================================
        Route::get('/videos/{date}/{unit}/{filename}', [VideoController::class, 'stream'])->name('video.stream');
        Route::get('/videos/check/{eventId}', [VideoController::class, 'checkVideo'])->name('events.check-video');
        
        // ========================================
        // ? REPORTS
        // ========================================
        Route::get('/reports/dsm', [ReportController::class, 'dsmPage'])->name('reports.dsm');
        
        // ========================================
        // ? ACTIVITY LOGS (Super Admin Only)
        // ========================================
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])
            ->middleware(['auth', 'super_admin'])
            ->name('activity.logs');

        // ========================================
        // ? NOTIFICATIONS
        // ========================================
        Route::get('/notifications', function () {
            return inertia('Notifications/Index');
        })->name('notifications.index');

        // ========================================
        // ? NOTIFICATIONS API Routes
        // ========================================
        Route::prefix('api/notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('api.notifications.index');
            Route::post('/{id}/read', [NotificationController::class, 'markAsRead'])->name('api.notifications.read');
            Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('api.notifications.mark-all');
            Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('api.notifications.destroy');
            Route::post('/bulk-delete', [NotificationController::class, 'bulkDelete'])->name('api.notifications.bulk-delete');
            Route::delete('/delete-all', [NotificationController::class, 'deleteAll'])->name('api.notifications.delete-all');
        });
        
        // ========================================
        // ? PROFILE
        // ========================================
        Route::get('/my-profile', function () {
            return \Inertia\Inertia::render('Profile/Show');
        })->name('profile.show');
    });
});