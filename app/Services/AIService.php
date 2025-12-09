<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
    }

    public function generatePost($category, $postType, $propertyData, $topPosts = null)
    {
        $systemPrompt = $this->buildSystemPrompt($category, $postType, $topPosts);
        $userPrompt = $this->buildUserPrompt($propertyData);

        try {
            if (empty($this->apiKey)) {
                // Fallback: Generate basic content without API
                return $this->generateFallbackContent($category, $postType, $propertyData);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model' => 'gpt-4',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $content = $response->json()['choices'][0]['message']['content'];
                return $this->parseGeneratedContent($content);
            }

            Log::error('AI API Error', ['response' => $response->body()]);
            return $this->generateFallbackContent($category, $postType, $propertyData);
        } catch (\Exception $e) {
            Log::error('AI Service Error', ['error' => $e->getMessage()]);
            return $this->generateFallbackContent($category, $postType, $propertyData);
        }
    }

    protected function buildSystemPrompt($category, $postType, $topPosts)
    {
        $prompt = "You are an expert social media content creator for a real estate company called Oweru. ";
        $prompt .= "Create engaging, professional posts for {$category} category. ";
        $prompt .= "Post type: {$postType}. ";

        if ($topPosts && $topPosts->count() > 0) {
            $prompt .= "\n\nHere are examples of successful posts:\n";
            foreach ($topPosts as $post) {
                $prompt .= "- Title: {$post->title}\n";
                $prompt .= "  Description: {$post->description}\n\n";
            }
            $prompt .= "Use these as inspiration but create original content.\n";
        }

        $prompt .= "\nReturn your response as JSON with 'title' and 'description' fields.";

        return $prompt;
    }

    protected function buildUserPrompt($propertyData)
    {
        if (empty($propertyData)) {
            return "Generate a creative and engaging social media post for real estate.";
        }

        $prompt = "Generate a social media post with the following property details:\n";
        $prompt .= json_encode($propertyData, JSON_PRETTY_PRINT);
        
        return $prompt;
    }

    protected function parseGeneratedContent($content)
    {
        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['title']) && isset($json['description'])) {
                return $json;
            }
        }

        // Fallback: extract title and description manually
        return [
            'title' => $this->extractTitle($content),
            'description' => $this->extractDescription($content),
        ];
    }

    protected function extractTitle($content)
    {
        if (preg_match('/title["\']?\s*:\s*["\']([^"\']+)["\']/i', $content, $matches)) {
            return $matches[1];
        }
        return "Amazing Property Opportunity";
    }

    protected function extractDescription($content)
    {
        if (preg_match('/description["\']?\s*:\s*["\']([^"\']+)["\']/i', $content, $matches)) {
            return $matches[1];
        }
        return $content;
    }

    protected function generateFallbackContent($category, $postType, $propertyData)
    {
        $categoryTitles = [
            'rentals' => 'Premium Rental Property Available',
            'property_sales' => 'Exclusive Property for Sale',
            'construction_property_management' => 'Professional Construction & Property Management',
            'lands_and_plots' => 'Prime Land & Plots Available',
            'property_services' => 'Expert Property Services',
            'investment' => 'Investment Opportunity',
        ];

        $title = $categoryTitles[$category] ?? 'Property Opportunity';
        
        $description = "Discover this amazing property opportunity with Oweru. ";
        $description .= "Contact us today for more information and to schedule a viewing.";

        if (!empty($propertyData)) {
            if (isset($propertyData['location'])) {
                $description = "Located in {$propertyData['location']}, " . $description;
            }
            if (isset($propertyData['price'])) {
                $description .= " Price: {$propertyData['price']}.";
            }
        }

        return [
            'title' => $title,
            'description' => $description,
        ];
    }

    public function improvePost(Post $post, $improvementType)
    {
        $prompt = "Improve the following post {$improvementType}:\n";
        $prompt .= "Title: {$post->title}\n";
        $prompt .= "Description: {$post->description}\n";
        $prompt .= "Make it more engaging and professional.";

        // Similar API call as generatePost
        return $this->generatePost($post->category, $post->post_type, [], null);
    }

    public function getSuggestions($category)
    {
        $posts = Post::where('category', $category)
            ->whereNotNull('performance_score')
            ->orderBy('performance_score', 'desc')
            ->limit(10)
            ->get(['title', 'description', 'performance_score']);

        return $posts;
    }
}

