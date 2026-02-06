<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (!auth()->user()->isAdmin()) {
            return response()->json(['error' => 'Unauthorized - Admin only'], 403);
        }

        return $next($request);
    }
}