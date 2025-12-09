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

    public function destroy($id)
    {
        $media = Media::findOrFail($id);
        
        Storage::disk('public')->delete($media->file_path);
        $media->delete();

        return response()->json(['message' => 'Media deleted successfully']);
    }
}

