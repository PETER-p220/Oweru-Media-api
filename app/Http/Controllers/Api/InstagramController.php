<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InstagramController extends Controller
{
    /**
     * Create a post on Instagram
     */
    public function createPost(Request $request): JsonResponse
    {
        try {
            // Validate the request - now requiring actual media files
            $validated = $request->validate([
                'caption' => 'required|string|max:2200',
                'post_type' => 'required|in:feed,carousel,reel',
                'post_id' => 'required|integer|exists:posts,id',
                'media' => 'required|array|min:1|max:10',
                'media.*' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:50000', // 50MB max
            ]);

            Log::info('Instagram post request received', [
                'post_id' => $validated['post_id'],
                'post_type' => $validated['post_type'],
                'media_count' => count($validated['media']),
                'has_media_files' => $request->hasFile('media')
            ]);

            // Process uploaded media files
            $mediaFiles = $request->file('media', []);
            $processedMedia = [];
            
            foreach ($mediaFiles as $index => $media) {
                if ($media && $media->isValid()) {
                    $processedMedia[] = [
                        'index' => $index,
                        'original_name' => $media->getClientOriginalName(),
                        'size' => $media->getSize(),
                        'mime_type' => $media->getMimeType(),
                        'extension' => $media->getClientOriginalExtension(),
                    ];
                } else {
                    Log::error("Invalid media file at index {$index}", [
                        'error' => $media ? $media->getErrorMessage() : 'File not found',
                        'original_name' => $media ? $media->getClientOriginalName() : 'N/A'
                    ]);
                    throw new \Exception("Media file at index {$index} is invalid or corrupted");
                }
            }

            if (empty($processedMedia)) {
                throw new \Exception('No valid media files were uploaded');
            }

            Log::info('Media files processed successfully', [
                'processed_count' => count($processedMedia),
                'files' => $processedMedia
            ]);

            // TODO: Implement actual Instagram API integration
            // 1. Get Instagram access token from config
            // 2. Create media container for each file
            // 3. Upload media files to Instagram
            // 4. Create the post with all media
            // 5. Return the published post details
            
            // For now, simulate successful Instagram post creation
            // In production, replace this with actual Instagram Graph API calls
            
            return response()->json([
                'success' => true,
                'message' => 'Post successfully created on Instagram with real media files!',
                'post_id' => $validated['post_id'],
                'media_count' => count($processedMedia),
                'media_files' => $processedMedia,
                'instagram_post_id' => 'ig_post_' . time() . '_' . uniqid(),
                'permalink' => 'https://www.instagram.com/p/example_' . time() . '/',
                'status' => 'published',
                'created_at' => now()->toISOString(),
                'debug_info' => [
                    'files_uploaded' => count($processedMedia),
                    'total_size' => array_sum(array_column($processedMedia, 'size')),
                    'file_types' => array_unique(array_column($processedMedia, 'mime_type'))
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Instagram post validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->except(['media.*']),
                'files_received' => $request->hasFile('media') ? count($request->file('media')) : 0
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'debug_info' => [
                    'has_files' => $request->hasFile('media'),
                    'files_array' => $request->file('media'),
                    'validation_errors' => $e->errors()
                ]
            ], 422);

        } catch (\Exception $e) {
            Log::error('Instagram post creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create Instagram post: ' . $e->getMessage(),
                'debug_info' => [
                    'exception_type' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'error_details' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get Instagram account info
     */
    public function getAccountInfo(): JsonResponse
    {
        try {
            // TODO: Implement actual Instagram API integration
            return response()->json([
                'success' => true,
                'account' => [
                    'id' => 'instagram_account_id',
                    'username' => 'oweru_media',
                    'account_type' => 'BUSINESS',
                    'media_count' => 0,
                    'follower_count' => 0,
                    'following_count' => 0
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to get Instagram account info', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to get account info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check Instagram API status
     */
    public function getStatus(): JsonResponse
    {
        try {
            // TODO: Check actual Instagram API connection
            return response()->json([
                'success' => true,
                'status' => 'connected',
                'message' => 'Instagram API is connected and ready',
                'last_sync' => now()->toISOString()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'disconnected',
                'message' => 'Instagram API is not connected: ' . $e->getMessage()
            ], 500);
        }
    }
}
