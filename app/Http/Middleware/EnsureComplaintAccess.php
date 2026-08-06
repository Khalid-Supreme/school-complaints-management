<?php

namespace App\Http\Middleware;

use App\Models\Complaint;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Centralized per-complaint access control.
 *
 * Allows:
 *   - admins and sub-admins
 *   - complaint officers
 *   - the complainant themselves (student or staff role)
 *
 * Usage:
 *   Route::get('/api/complaints/{complaint}', ...)->middleware('complaint.access');
 */
class EnsureComplaintAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Resolve the complaint from the route binding (works for {complaint} param).
        $complaint = $request->route('complaint');
        if (!$complaint instanceof Complaint) {
            // Fallback: try manual lookup if binding failed.
            $id = $request->route('complaint') ?? $request->route('id');
            if ($id) {
                $complaint = Complaint::find($id);
            }
        }

        if (!$complaint) {
            return response()->json(['message' => 'Complaint not found.'], 404);
        }

        $slug = $user->role?->slug;

        // Allowed roles for read/write operations
        if (in_array($slug, ['admin', 'sub_admin', 'complaint_officer'], true)) {
            return $next($request);
        }

        // Complainants (student/staff) can access ONLY their own complaints
        if (in_array($slug, ['student', 'staff'], true)
            && (int) $user->id === (int) $complaint->complainant_id) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden — you do not have access to this complaint.',
        ], 403);
    }
}
