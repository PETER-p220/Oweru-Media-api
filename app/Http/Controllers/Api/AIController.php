<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\AITrainingData;
use App\Models\AIGeneration;
use App\Services\AIService;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'post_type' => 'required|in:Static,Carousel,Reel',
            'property_data' => 'nullable|array',
        ]);

        // Get top performing posts for context
        $topPosts = Post::where('category', $validated['category'])
            ->whereNotNull('performance_score')
            ->where('performance_score', '>', 70)
            ->orderBy('performance_score', 'desc')
            ->limit(5)
            ->get();

        // Generate content using AI service
        try {
            $generated = $this->aiService->generatePost(
                $validated['category'],
                $validated['post_type'],
                $validated['property_data'] ?? [],
                $topPosts
            );

            // Store generation for learning
            AIGeneration::create([
                'user_id' => auth()->id(),
                'category' => $validated['category'],
                'prompt' => json_encode($validated),
                'generated_content' => json_encode($generated),
                'model_used' => 'gpt-4',
            ]);

            // Create a pending post draft for moderator approval
            $post = Post::create([
                'user_id' => auth()->id(),
                'category' => $validated['category'],
                'post_type' => $validated['post_type'],
                'title' => $generated['title'] ?? 'Generated Post',
                'description' => $generated['description'] ?? '',
                'metadata' => $validated['property_data'] ?? [],
                'ai_generated' => true,
                'status' => 'pending',
            ]);

            return response()->json([
                'draft' => $post->load(['user', 'media', 'moderator']),
                'generated' => $generated,
                'message' => 'Draft created and awaiting moderation',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI generation failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function improve(Request $request)
    {
        $validated = $request->validate([
            'post_id' => 'required|exists:posts,id',
            'improvement_type' => 'required|in:title,description,both',
        ]);

        $post = Post::findOrFail($validated['post_id']);
        
        try {
            $improved = $this->aiService->improvePost(
                $post,
                $validated['improvement_type']
            );

            return response()->json($improved);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'AI improvement failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getSuggestions($category)
    {
        $suggestions = $this->aiService->getSuggestions($category);

        return response()->json($suggestions);
    }
}

