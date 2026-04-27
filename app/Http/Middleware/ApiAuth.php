<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if Authorization header exists
        if (!$request->bearerToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please provide a valid Bearer token.',
                'error' => 'Authorization header missing'
            ], 401);
        }

        // Let Laravel's built-in authentication handle the token validation
        // The auth:api middleware will handle this automatically
        $response = $next($request);
        
        // Check if the response is a 401 Unauthorized from Laravel's auth:api middleware
        if ($response->getStatusCode() === 401) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired authentication token. Please login again.',
                'error' => 'Unauthorized access'
            ], 401);
        }
        
        return $response;
    }
}
