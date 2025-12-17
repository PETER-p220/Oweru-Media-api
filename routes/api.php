<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Posts
    Route::apiResource('posts', PostController::class);
    Route::get('/posts/category/{category}', [PostController::class, 'getByCategory']);
    Route::post('/posts/{post}/approve', [PostController::class, 'approve']);
    Route::post('/posts/{post}/reject', [PostController::class, 'reject']);
    
    // AI Generation
    Route::post('/ai/generate', [AIController::class, 'generate']);
    Route::post('/ai/improve', [AIController::class, 'improve']);
    Route::get('/ai/suggestions/{category}', [AIController::class, 'getSuggestions']);
    
    // Media
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);
});

