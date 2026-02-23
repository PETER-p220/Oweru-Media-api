<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function upload(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file',
            'post_id' => 'nullable|exists:posts,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');

        $media = Media::create([
            'post_id' => $validated['post_id'] ?? null,
            'file_path' => $path,
            'file_type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'video',
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'id' => $media->id,
            'url' => Storage::url($path),
            'type' => $media->file_type,
        ], 201);
    }

    public function download(Request $request)
    {
        $url = $request->query('url');
        $filename = $request->query('filename');
        
        if (!$url || !$filename) {
            return response()->json(['error' => 'URL and filename are required'], 400);
        }
        
        try {
            // Decode and validate URL
            $decodedUrl = urldecode($url);
            if (!str_starts_with($decodedUrl, 'http://localhost:8000/storage/')) {
                return response()->json(['error' => 'Invalid URL'], 400);
            }
            
            // Extract file path
            $filePath = str_replace('http://localhost:8000/storage/', '', $decodedUrl);
            $fullPath = storage_path('app/public/' . $filePath);
            
            if (!file_exists($fullPath)) {
                return response()->json(['error' => 'File not found'], 404);
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
            
            // Create download response
            return response()->download($fullPath, $filename, [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
                'Cache-Control' => 'public, max-age=31536000'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error: ' . $e->getMessage(),
                'url' => $url,
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }
}
