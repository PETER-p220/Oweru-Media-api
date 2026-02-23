<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Handle preflight OPTIONS requests
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 200);
        } else {
            $response = $next($request);
        }
        
        // Add CORS headers - check if response has headers method
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', '*')
                      ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                      ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                      ->header('Access-Control-Allow-Credentials', 'true')
                      ->header('Access-Control-Max-Age', '86400'); // 24 hours
        } else {
            // Handle BinaryFileResponse (from download method)
            $response->headers->set('Access-Control-Allow-Origin', '*');
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours
        }
        
        return $response;
    }
}
