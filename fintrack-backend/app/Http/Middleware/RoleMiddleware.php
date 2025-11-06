<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = auth()->user();

        // For admin role, check isAdmin method
        if ($role === 'admin' && !$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden - Admin access required',
            ], 403);
        }

        // For other roles, check exact role match
        if ($role !== 'admin' && $user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => "Forbidden - {$role} access required",
            ], 403);
        }

        return $next($request);
    }
}
