<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ComplaintAssignmentController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\DiagnosticsController;
use App\Http\Controllers\Api\SecurityDashboardController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])
    ->middleware(['audit.context', 'ips', 'throttle:login']);
Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail'])
    ->middleware(['audit.context', 'ips', 'throttle:password-reset']);
Route::post('/reset-password', [AuthController::class, 'reset'])
    ->middleware(['audit.context', 'ips', 'throttle:password-reset']);
Route::get('/departments', [DepartmentController::class, 'index']);
Route::post('/register/student', [RegisterController::class, 'registerStudent'])
    ->middleware(['audit.context', 'ips', 'throttle:register']);
Route::post('/register/staff', [RegisterController::class, 'registerStaff'])
    ->middleware(['audit.context', 'ips', 'throttle:register']);
Route::get('/register/verify/{user}/{hash}', [RegisterController::class, 'verify'])
    ->name('verification.verify')
    ->middleware(['signed', 'audit.context']);
Route::post('/register/resend', [RegisterController::class, 'resend'])
    ->middleware(['audit.context', 'ips', 'throttle:register']);

// Public settings (readable without auth for landing/login pages)
Route::get('/settings', [SettingsController::class, 'index']);

Route::middleware(['auth:sanctum', 'audit.context', 'ips', 'password.changed'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/password-change/status', [AuthController::class, 'passwordChangeStatus']);
    Route::post('/password-change/verify', [AuthController::class, 'verifyPasswordChange'])
        ->middleware('throttle:password-change-verify');
    Route::post('/password-change/verify/resend', [AuthController::class, 'resendPasswordChange'])
        ->middleware('throttle:password-change-resend');

    // Complaints
    Route::get('/categories', [ComplaintController::class, 'categories']);

    // Status updates: complaint_officer (admins/sub_admins also allowed).
    // Scoped to the officer assigned to the complaint via complaint.access.
    Route::patch('/complaints/{complaint}/status', [ComplaintController::class, 'updateStatus'])
        ->middleware(['role:admin,complaint_officer', 'complaint.access']);

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
        ->middleware(['role:admin,complaint_officer', 'complaint.access']);
    Route::get('/staff/assignments', [ComplaintAssignmentController::class, 'myAssignments'])
        ->middleware('role:complaint_officer');
    Route::get('/staff/list', [ComplaintAssignmentController::class, 'staffList'])
        ->middleware('role:admin');

    // Admin Dashboard (admin or sub_admin)
    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
        ->middleware('role:admin,sub_admin');

    // User Management
    // Read-only list/show: admin or sub_admin.
    Route::middleware('role:admin,sub_admin')->prefix('/admin/users')->group(function () {
        Route::get('/students', [UserManagementController::class, 'students']);
        Route::get('/staff', [UserManagementController::class, 'staff']);
        Route::get('/{user}', [UserManagementController::class, 'show']);
    });

    // Mutations (create/update/delete/role/password): admin only.
    Route::middleware('role:admin')->prefix('/admin/users')->group(function () {
        Route::post('/', [UserManagementController::class, 'store']);
        Route::put('/{user}', [UserManagementController::class, 'update']);
        Route::delete('/{user}', [UserManagementController::class, 'destroy']);
        Route::patch('/{user}/role', [UserManagementController::class, 'updateRole']);
        Route::post('/{user}/reset-password', [UserManagementController::class, 'resetPassword']);
    });

    // Security Dashboard (Admin, Sub-Admin, or Security role)
    Route::middleware(['role:admin,security'])->group(function () {
        Route::get('/security/dashboard', [SecurityDashboardController::class, 'index']);
        Route::get('/security/blocked-ips', [SecurityDashboardController::class, 'blockedIps']);
        Route::get('/security/blocked-users', [SecurityDashboardController::class, 'blockedUsers']);
        Route::get('/security/audit/logins', [SecurityDashboardController::class, 'loginAudit']);
        Route::post('/security/ips/unblock', [SecurityDashboardController::class, 'unblockIp']);
        Route::post('/security/users/unblock', [SecurityDashboardController::class, 'unblockUser']);
    });

    // Audit log (super admin only)
    Route::get('/admin/audit/logs', [AuditLogController::class, 'index'])
        ->middleware('role:admin');

    // Diagnostics (super admin only) — request IP/proxy correlation
    Route::get('/diagnostics/request-context', [DiagnosticsController::class, 'requestContext'])
        ->middleware('role:admin');

    Route::middleware('role:admin,sub_admin')->prefix('/settings')->group(function () {
        Route::put('/', [SettingsController::class, 'update']);
    });
});
