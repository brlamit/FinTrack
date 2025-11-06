<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ApiValidationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only validate API requests
        if (!$request->is('api/*')) {
            return $next($request);
        }

        // Add any global API validation rules here
        // For example, check for required headers, rate limiting, etc.

        // Validate common API parameters
        $validator = Validator::make($request->all(), [
            // Add global validation rules if needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $response = $next($request);

        // Add common response headers
        $response->headers->set('X-API-Version', '1.0');
        $response->headers->set('X-Powered-By', 'FinTrack API');

        return $response;
    }
}