<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\ComplaintAssignmentController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\SecurityDashboardController;
use App\Http\Controllers\Api\AdminDashboardController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'ips'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Complaints
    Route::get('/categories', [ComplaintController::class, 'categories']);

    // Status updates: ONLY complaint_officer (admins also allowed via EnsureRole)
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])
        ->middleware('role:complaint_officer');

    // Show / store / index
    Route::get('/complaints/{complaint}', [ComplaintController::class, 'show'])
        ->middleware('complaint.access');
    Route::post('/complaints', [ComplaintController::class, 'store'])
        ->middleware('role:student,staff');
    Route::get('/complaints', [ComplaintController::class, 'index']);

    // Chat
    Route::get('/complaints/{complaint}/messages', [ChatController::class, 'index'])
        ->middleware('complaint.access');
    Route::post('/complaints/{complaint}/messages', [ChatController::class, 'store'])
        ->middleware('complaint.access');

    // Attachments
    Route::get('/complaints/{complaint}/attachments', [AttachmentController::class, 'index'])
        ->middleware('complaint.access');
    Route::post('/complaints/{complaint}/attachments', [AttachmentController::class, 'store'])
        ->middleware('complaint.access');
    Route::get('/complaints/{complaint}/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->middleware('complaint.access');

    // Assignments (admin-only via role middleware)
    Route::post('/complaints/{complaint}/assign', [ComplaintAssignmentController::class, 'assign'])
        ->middleware('role:admin');
    Route::get('/complaints/{complaint}/assignments', [ComplaintAssignmentController::class, 'history'])
        ->middleware('role:admin,complaint_officer');
    Route::get('/staff/assignments', [ComplaintAssignmentController::class, 'myAssignments'])
        ->middleware('role:complaint_officer');
    Route::get('/staff/list', [ComplaintAssignmentController::class, 'staffList'])
        ->middleware('role:admin');

    // Admin Dashboard (admin only)
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('role:admin');

    // Security Dashboard (Admin or Security role)
    Route::middleware(['role:admin,security'])->group(function () {
        Route::get('/security/dashboard', [SecurityDashboardController::class, 'index']);
        Route::get('/security/audit/logins', [SecurityDashboardController::class, 'loginAudit']);
    });
});
