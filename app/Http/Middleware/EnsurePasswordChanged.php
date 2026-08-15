<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EnsurePasswordChanged
{
    /**
     * Block authenticated API requests while the user must change their
     * password (admin-created or admin-reset accounts). The change-password
     * endpoint, logout, and the session-restore user endpoint are always
     * allowed so the user can act and the SPA can detect the flag.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->must_change_password) {
            $allowed = [
                'api/change-password',
                'api/logout',
                'api/user',
            ];

            $path = ltrim($request->path(), '/');

            if (! in_array($path, $allowed, true)) {
                return response()->json([
                    'message' => 'You must change your password before continuing.',
                    'must_change_password' => true,
                ], 403);
            }
        }

        if ($user && $user->password_change_pending_verification) {
            $allowedWhilePending = [
                'api/password-change/verify',
                'api/password-change/verify/resend',
                'api/password-change/status',
                'api/logout',
                'api/user',
            ];

            $path = ltrim($request->path(), '/');

            if (! in_array($path, $allowedWhilePending, true)) {
                return response()->json([
                    'message' => 'You must verify your password change before continuing.',
                    'password_change_pending_verification' => true,
                ], 403);
            }
        }

        return $next($request);
    }
}
