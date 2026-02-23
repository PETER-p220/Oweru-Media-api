<?php

use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\AIController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\InstagramController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/posts/approved', [PostController::class, 'getApproved']); // Public endpoint for approved posts
Route::get('/posts/approved/{id}', [PostController::class, 'getApprovedPost']); // Public endpoint for single approved post
Route::post('/contact', [ContactController::class, 'submit']); // Public contact form endpoint

// Instagram API routes (public for now, but should be protected in production)
Route::prefix('instagram')->group(function () {
    Route::post('/post', [InstagramController::class, 'createPost']);
    Route::get('/account', [InstagramController::class, 'getAccountInfo']);
    Route::get('/status', [InstagramController::class, 'getStatus']);
});

// Media proxy routes to bypass CORS issues
Route::get('/proxy/media/{path}', function ($path) {
    try {
        // Construct full path
        $fullPath = storage_path('app/public/' . $path);
        
        // Check if file exists
        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'File not found: ' . $path], 404);
        }
        
        // Check if it's a file
        if (!is_file($fullPath)) {
            return response()->json(['error' => 'Not a file'], 400);
        }
        
        // Get file info
        $fileSize = filesize($fullPath);
        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        
        // Determine MIME type
        $mimeType = 'application/octet-stream';
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $mimeType = 'image/jpeg';
                break;
            case 'png':
                $mimeType = 'image/png';
                break;
            case 'gif':
                $mimeType = 'image/gif';
                break;
            case 'mp4':
                $mimeType = 'video/mp4';
                break;
            case 'mov':
                $mimeType = 'video/quicktime';
                break;
            case 'avi':
                $mimeType = 'video/x-msvideo';
                break;
            case 'webm':
                $mimeType = 'video/webm';
                break;
        }
        
        // Create response with manual headers
        $response = new \Illuminate\Http\Response(
            file_get_contents($fullPath),
            200,
            [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Cache-Control' => 'public, max-age=31536000',
                'Content-Disposition' => 'inline; filename="' . basename($fullPath) . '"'
            ]
        );
        
        return $response;
        
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error: ' . $e->getMessage(),
            'path' => $path,
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->where('path', '.*');

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
    
    
    // Contacts (Admin & Moderator only)
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
});
// Media routes
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::get('/media/download', [MediaController::class, 'download']);
    Route::delete('/media/{id}', [MediaController::class, 'destroy']);
    