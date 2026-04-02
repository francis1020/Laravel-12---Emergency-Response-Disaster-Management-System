<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserTypeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$types): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized. Please log in.');
        }

        // Convert types to integers for comparison
        $types = array_map('intval', $types);

        // Check if user's type matches any of the required types
        if (!in_array($user->type, $types)) {
            abort(403, 'Access denied. Insufficient privileges.');
        }

        return $next($request);
    }
}

