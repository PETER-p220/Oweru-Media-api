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
        try {
            $status = $request->query('status');

            $query = Post::with(['user', 'media', 'moderator'])->latest();

            if ($status && $status !== 'all') {
                $query->where('status', $status);
            }

            $posts = $query->paginate(15);

            return response()->json($posts);
        } catch (\Exception $e) {
            \Log::error('Error in PostController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error fetching posts: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category'   => 'required|string',
            'post_type'  => 'required|in:Static,Carousel,Reel',
            'title'      => 'required|string|max:255',
            'description'=> 'required|string',
            'metadata'   => 'nullable|array',
            'images'     => 'nullable|array',
            'images.*'   => 'image|max:10240',
            'video'      => 'nullable|file|mimes:mp4,avi,mov,webm|max:102400',
        ]);

        $post = Post::create([
            'user_id'      => auth()->id(),
            'category'     => $validated['category'],
            'post_type'    => $validated['post_type'],
            'title'        => $validated['title'],
            'description'  => $validated['description'],
            'status'       => 'pending',
            'metadata'     => $validated['metadata'] ?? [],
            'ai_generated' => $request->boolean('ai_generated', false),
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('posts/images', 'public');
                $post->media()->create([
                    'file_path' => $path,
                    'file_type' => 'image',
                    'mime_type' => $image->getMimeType(),
                    'file_size' => $image->getSize(),
                    'order'     => $index,
                ]);
            }
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $path  = $video->store('posts/videos', 'public');
            $post->media()->create([
                'file_path' => $path,
                'file_type' => 'video',
                'mime_type' => $video->getMimeType(),
                'file_size' => $video->getSize(),
                'order'     => 0,
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

        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'metadata'    => 'nullable|array',
        ]);

        if ($post->status === 'rejected') {
            $validated['status']          = 'pending';
            $validated['moderated_by']    = null;
            $validated['moderation_note'] = null;
        }

        $post->update($validated);

        return response()->json($post->load(['media', 'user', 'moderator']));
    }

    public function destroy(Post $post)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && $post->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $postId = $post->id;

        \DB::beginTransaction();

        try {
            // Delete physical media files from storage
            foreach ($post->media()->get() as $media) {
                if ($media->file_path && Storage::disk('public')->exists($media->file_path)) {
                    Storage::disk('public')->delete($media->file_path);
                }
            }

            // Delete media records
            $post->media()->delete();

            // Delete the post — forceDelete() works whether or not SoftDeletes is on the model
            $post->forceDelete();

            \DB::commit();

            return response()->json(['message' => 'Post deleted successfully'], 200);

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Post deletion failed', [
                'post_id' => $postId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['message' => 'Error deleting post: ' . $e->getMessage()], 500);
        }
    }

    public function getByCategory($category, Request $request)
    {
        $status = $request->query('status', 'all');

        $query = Post::with(['user', 'media', 'moderator'])
            ->where('category', $category)
            ->latest();

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        return response()->json($query->paginate(15));
    }

    public function approve(Request $request, $postId)
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update([
            'status'          => 'approved',
            'moderated_by'    => $user->id,
            'moderation_note' => $request->input('moderation_note'),
        ]);

        return response()->json(['message' => 'Post approved successfully']);
    }

    public function reject(Request $request, $postId)
    {
        $user = $request->user();
        if (!$user || !$user->isModerator()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $post = Post::find($postId);
        if (!$post) {
            return response()->json(['message' => 'Post not found'], 404);
        }

        $post->update([
            'status'          => 'rejected',
            'moderated_by'    => $user->id,
            'moderation_note' => $request->input('moderation_note'),
        ]);

        return response()->json(['message' => 'Post rejected successfully']);
    }

    /**
     * Public — pending posts for moderator dashboard
     */
    public function getPending(Request $request)
    {
        try {
            $posts = Post::with(['user', 'media', 'moderator'])
                ->where('status', 'pending')
                ->latest()
                ->paginate(15);

            return response()->json($posts);
        } catch (\Exception $e) {
            \Log::error('Error fetching pending posts: ' . $e->getMessage());
            return response()->json([
                'data'    => [],
                'message' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public — approved posts for home page
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
                'data'    => [],
                'message' => 'Error fetching posts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Public — single approved post for sharing
     */
    public function getApprovedPost($id)
    {
        try {
            $post = Post::with(['media'])
                ->where('status', 'approved')
                ->where('id', $id)
                ->first();

            if (!$post) {
                return response()->json(['message' => 'Post not found or not approved'], 404);
            }

            return response()->json($post);
        } catch (\Exception $e) {
            \Log::error('Error fetching approved post: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching post: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Public — basic statistics
     */
    public function getStats(Request $request)
    {
        try {
            return response()->json([
                'totalPosts'       => Post::count(),
                'approvedPosts'    => Post::where('status', 'approved')->count(),
                'pendingPosts'     => Post::where('status', 'pending')->count(),
                'rejectedPosts'    => Post::where('status', 'rejected')->count(),
                'totalCategories'  => 6,
                'recentPosts'      => min(5, Post::where('status', 'approved')->count()),
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching stats: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching statistics: ' . $e->getMessage()], 500);
        }
    }
}