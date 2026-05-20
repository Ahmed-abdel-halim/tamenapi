<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RestrictDeletionsToAdmin
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
        // Check if the request is a DELETE request
        if ($request->isMethod('delete')) {
            // Attempt standard auth, fallback to sanctum guard for non-middleware routes, or custom X-User-Id header
            $user = auth()->user() ?? auth('sanctum')->user();

            // Fallback user identification matching the app's standard check (CheckBlockedAgent style)
            if (!$user) {
                $userId = $request->header('X-User-Id') ?? $request->input('user_id');
                if ($userId) {
                    $user = \App\Models\User::find($userId);
                }
            }

            // If no user is found, or the user is not an administrator, deny deletion with a 403 response
            if (!$user || !$user->is_admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، لا تمتلك الصلاحية لحذف العناصر. هذا الإجراء متاح فقط لمدير النظام (الآدمن).'
                ], 403);
            }
        }

        return $next($request);
    }
}
