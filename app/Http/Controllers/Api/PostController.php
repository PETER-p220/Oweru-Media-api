<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user', 'media'])
            ->latest()
            ->paginate(15);
        
        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'post_type' => 'required|in:Static,Carousel,Reel',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'metadata' => 'nullable|array',
            'images' => 'nullable|array',
            'images.*' => 'image|max:10240',
            'video' => 'nullable|file|mimes:mp4,avi,mov,webm|max:102400',
        ]);

        $post = Post::create([
            'user_id' => auth()->id(),
            'category' => $validated['category'],
            'post_type' => $validated['post_type'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'metadata' => $validated['metadata'] ?? [],
        ]);

        // Handle file uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('posts/images', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'file_type' => 'image',
                    'mime_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'order' => $index,
                ]);
            }
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path = $video->store('posts/videos', 'public');
            $post->media()->create([
                'file_path' => $path,
                'file_type' => 'video',
                'mime_type' => $video->getMimeType(),
                'file_size' => $video->getSize(),
                'order' => 0,
            ]);
        }

        return response()->json($post->load('media'), 201);
    }

    public function show(Post $post)
    {
        return response()->json($post->load(['user', 'media']));
    }

    public function update(Request $request, Post $post)
    {
        // Check if user owns the post
        if ($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'metadata' => 'nullable|array',
        ]);

        $post->update($validated);

        return response()->json($post->load('media'));
    }

    public function destroy(Post $post)
    {
        // Check if user owns the post
        if ($post->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated media files
        foreach ($post->media as $media) {
            Storage::disk('public')->delete($media->file_path);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function getByCategory($category)
    {
        $posts = Post::with(['user', 'media'])
            ->where('category', $category)
            ->latest()
            ->paginate(15);
        
        return response()->json($posts);
    }
}

