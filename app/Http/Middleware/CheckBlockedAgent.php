<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBlockedAgent
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        // If user is not authenticated via sanctum, try to get from header or query param
        // (This app uses X-User-Id header for many of its manual "authentication" checks)
        if (!$user) {
            $userId = $request->header('X-User-Id') ?? $request->input('user_id');
            if ($userId) {
                $user = \App\Models\User::find($userId);
            }
        }

        // If user is found and blocked
        if ($user && $user->is_blocked) {
            // Block data modification requests (POST, PUT, PATCH, DELETE)
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                return response()->json([
                    'message' => 'لقد تم حظرك مؤقتا من المنظومة حتى تسدد الديون التي عليك'
                ], 403);
            }
        }

        return $next($request);
    }
}
