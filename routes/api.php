<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\ComplaintAssignmentController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AttachmentController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Complaints
    Route::get('/categories', [ComplaintController::class, 'categories']);
    Route::apiResource('complaints', ComplaintController::class)->only(['index', 'store', 'show']);
    
    // Chat
    Route::get('/complaints/{complaint}/messages', [ChatController::class, 'index']);
    Route::post('/complaints/{complaint}/messages', [ChatController::class, 'store']);
    
    // Attachments
    Route::get('/complaints/{complaint}/attachments', [AttachmentController::class, 'index']);
    Route::post('/complaints/{complaint}/attachments', [AttachmentController::class, 'store']);
    
    // Assignments
    Route::post('/complaints/{complaint}/assign', [ComplaintAssignmentController::class, 'assign']);
    Route::get('/complaints/{complaint}/assignments', [ComplaintAssignmentController::class, 'history']);
    Route::get('/staff/assignments', [ComplaintAssignmentController::class, 'myAssignments']);
    Route::get('/staff/list', [ComplaintAssignmentController::class, 'staffList']);
});
