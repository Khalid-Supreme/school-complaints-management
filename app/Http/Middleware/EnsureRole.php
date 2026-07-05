<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Centralized role-based access control middleware.
 *
 * Usage:
 *   Route::middleware('role:admin')->...
 *   Route::middleware('role:admin,complaint_officer')->...
 */
class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string ...$roles  One or more role slugs that are allowed to proceed.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $userRoleSlug = $user->role?->slug;

        if (!$userRoleSlug) {
            return response()->json(['message' => 'Forbidden — no role assigned'], 403);
        }

        // Allow always when 'admin' is in the allowed list — admins are super-users.
        if (in_array('admin', $roles, true) && $userRoleSlug === 'admin') {
            return $next($request);
        }

        if (!in_array($userRoleSlug, $roles, true)) {
            return response()->json([
                'message' => 'Forbidden — your role cannot perform this action.',
                'allowed_roles' => $roles,
                'your_role' => $userRoleSlug,
            ], 403);
        }

        return $next($request);
    }
}
