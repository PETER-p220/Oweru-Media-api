<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');

        $query = Post::with(['user', 'media', 'moderator'])->latest();

        // Only filter by status if explicitly provided and not 'all'
        // For admin/moderator views, show all posts when status is 'all'
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        // If status is 'all' or not provided, show all posts (no status filter)

        $posts = $query->paginate(15);
        
        // Debug: Log what we're returning
        \Log::info('Index method called with status:', ['status' => $status]);
        \Log::info('Posts count:', ['count' => $posts->count()]);
        \Log::info('Posts data:', ['posts' => $posts->items()]);
        
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
            'status' => 'pending',
            'metadata' => $validated['metadata'] ?? [],
            'ai_generated' => $request->boolean('ai_generated', false),
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

        return response()->json($post->load(['media', 'user', 'moderator']), 201);
    }

    public function show(Post $post)
    {
        return response()->json($post->load(['user', 'media']));
    }

    public function update(Request $request, Post $post)
    {
        $user = $request->user();
        
        // Allow admins to update any post, or users to update their own posts
        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'metadata' => 'nullable|array',
        ]);

        // If a previously rejected post is updated by its owner (admin), send it back to pending
        if ($post->status === 'rejected') {
            $validated['status'] = 'pending';
            $validated['moderated_by'] = null;
            $validated['moderation_note'] = null;
        }

        $post->update($validated);

        return response()->json($post->load(['media', 'user', 'moderator']));
    }

    public function destroy(Post $post)
    {
        $user = auth()->user();
        
        // Allow admins to delete any post, or users to delete their own posts
        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete associated media files
        foreach ($post->media as $media) {
            Storage::disk('public')->delete($media->file_path);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully']);
    }

    public function getByCategory($category, Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Post::with(['user', 'media', 'moderator'])
            ->where('category', $category)
            ->latest();

        // Only filter by status if explicitly provided and not 'all'
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $posts = $query->paginate(15);
        
        return response()->json($posts);
    }

    public function approve(Request $request, $postId)
    {
        $user = $request->user();
        if (! $user || ! $user->isModerator()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find post manually
        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update([
            'status' => 'approved',
            'moderated_by' => $user->id,
            'moderation_note' => $request->input('moderation_note'),
        ]);

        return response()->json(['message' => 'Post approved successfully']);
    }

    public function reject(Request $request, $postId)
    {
        $user = $request->user();
        if (! $user || ! $user->isModerator()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Find post manually
        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update([
            'status' => 'rejected',
            'moderated_by' => $user->id,
            'moderation_note' => $request->input('moderation_note'),
        ]);

        return response()->json(['message' => 'Post rejected successfully']);
    }

    /**
     * Public endpoint to get only approved posts
     * This is accessible without authentication for the home page
     */
    public function getApproved(Request $request)
    {
        try {
            $posts = Post::with(['media'])
                ->where('status', 'approved')
                ->latest()
                ->paginate(15);
            
            return response()->json($posts);
        } catch (\Exception $e) {
            \Log::error('Error fetching approved posts: ' . $e->getMessage());
            return response()->json([
                'data' => [],
                'message' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public endpoint to get a single approved post by ID
     * This is accessible without authentication for sharing posts
     */
    public function getApprovedPost($id)
    {
        try {
            $post = Post::with(['media'])
                ->where('status', 'approved')
                ->where('id', $id)
                ->first();
            
            if (!$post) {
                return response()->json([
                    'message' => 'Post not found or not approved'
                ], 404);
            }
            
            return response()->json($post);
        } catch (\Exception $e) {
            \Log::error('Error fetching approved post: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching post: ' . $e->getMessage()
            ], 500);
        }
    }
}

