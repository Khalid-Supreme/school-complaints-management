<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Complaints
    Route::get('/categories', [\App\Http\Controllers\Api\ComplaintController::class, 'categories']);
    Route::apiResource('complaints', \App\Http\Controllers\Api\ComplaintController::class)->only(['index', 'store', 'show']);
});
