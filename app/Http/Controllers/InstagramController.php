<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Facebook\Facebook;
use Illuminate\Support\Facades\Storage;

class InstagramController extends Controller
{
    private $fb;
    private $accessToken;
    private $instagramAccountId;

    public function __construct()
    {
        $this->fb = new Facebook([
            'app_id' => env('FACEBOOK_APP_ID'),
            'app_secret' => env('FACEBOOK_APP_SECRET'),
            'default_graph_version' => 'v18.0',
        ]);

        $this->accessToken = env('INSTAGRAM_ACCESS_TOKEN');
        $this->instagramAccountId = env('INSTAGRAM_ACCOUNT_ID');
    }

    public function createPost(Request $request)
    {
        $request->validate([
            'media' => 'required|array',
            'media.*' => 'file|mimes:jpeg,png,jpg,mp4,mov|max:102400',
            'caption' => 'required|string|max:2200',
            'post_type' => 'required|in:feed,carousel,reel'
        ]);

        try {
            $postType = $request->post_type;
            $caption = $request->caption;

            if ($postType === 'carousel') {
                return $this->createCarouselPost($request, $caption);
            } elseif ($postType === 'reel') {
                return $this->createReelPost($request, $caption);
            } else {
                return $this->createSinglePost($request, $caption);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function createSinglePost(Request $request, $caption)
    {
        $media = $request->file('media')[0];
        $isVideo = in_array($media->extension(), ['mp4', 'mov']);

        // Upload media to your server first
        $path = $media->store('instagram', 'public');
        $mediaUrl = url(Storage::url($path));

        // Create container
        $endpoint = "/{$this->instagramAccountId}/media";
        $params = [
            'caption' => $caption,
            'access_token' => $this->accessToken,
        ];

        if ($isVideo) {
            $params['media_type'] = 'REELS';
            $params['video_url'] = $mediaUrl;
        } else {
            $params['image_url'] = $mediaUrl;
        }

        $response = $this->fb->post($endpoint, $params);
        $creationId = $response->getGraphNode()['id'];

        // Wait for processing (for videos)
        if ($isVideo) {
            sleep(30); // Wait for video processing
        }

        // Publish the post
        $publishEndpoint = "/{$this->instagramAccountId}/media_publish";
        $publishResponse = $this->fb->post($publishEndpoint, [
            'creation_id' => $creationId,
            'access_token' => $this->accessToken,
        ]);

        $postId = $publishResponse->getGraphNode()['id'];

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'post_id' => $postId,
            'permalink' => "https://www.instagram.com/p/{$postId}/"
        ]);
    }

    private function createCarouselPost(Request $request, $caption)
    {
        $mediaItems = [];

        // Step 1: Create containers for each media item
        foreach ($request->file('media') as $media) {
            $path = $media->store('instagram', 'public');
            $mediaUrl = url(Storage::url($path));

            $response = $this->fb->post("/{$this->instagramAccountId}/media", [
                'image_url' => $mediaUrl,
                'is_carousel_item' => true,
                'access_token' => $this->accessToken,
            ]);

            $mediaItems[] = $response->getGraphNode()['id'];
        }

        // Step 2: Create carousel container
        $carouselResponse = $this->fb->post("/{$this->instagramAccountId}/media", [
            'caption' => $caption,
            'media_type' => 'CAROUSEL',
            'children' => implode(',', $mediaItems),
            'access_token' => $this->accessToken,
        ]);

        $carouselId = $carouselResponse->getGraphNode()['id'];

        // Step 3: Publish carousel
        $publishResponse = $this->fb->post("/{$this->instagramAccountId}/media_publish", [
            'creation_id' => $carouselId,
            'access_token' => $this->accessToken,
        ]);

        $postId = $publishResponse->getGraphNode()['id'];

        return response()->json([
            'success' => true,
            'message' => 'Carousel post created successfully',
            'post_id' => $postId,
            'permalink' => "https://www.instagram.com/p/{$postId}/"
        ]);
    }

    private function createReelPost(Request $request, $caption)
    {
        $video = $request->file('media')[0];
        $path = $video->store('instagram', 'public');
        $videoUrl = url(Storage::url($path));

        // Create Reel container
        $response = $this->fb->post("/{$this->instagramAccountId}/media", [
            'media_type' => 'REELS',
            'video_url' => $videoUrl,
            'caption' => $caption,
            'share_to_feed' => true,
            'access_token' => $this->accessToken,
        ]);

        $creationId = $response->getGraphNode()['id'];

        // Wait for video processing
        sleep(60);

        // Publish Reel
        $publishResponse = $this->fb->post("/{$this->instagramAccountId}/media_publish", [
            'creation_id' => $creationId,
            'access_token' => $this->accessToken,
        ]);

        $postId = $publishResponse->getGraphNode()['id'];

        return response()->json([
            'success' => true,
            'message' => 'Reel created successfully',
            'post_id' => $postId,
            'permalink' => "https://www.instagram.com/reel/{$postId}/"
        ]);
    }
}