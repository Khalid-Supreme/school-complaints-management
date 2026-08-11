<?php

namespace App\Http\Middleware;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Centralized per-complaint access control.
 *
 * Allows:
 *   - admins and sub-admins
 *   - complaint officers assigned to the complaint
 *   - the complainant themselves (student or staff role)
 *
 * Usage:
 *   Route::get('/api/complaints/{complaint}', ...)->middleware('complaint.access');
 */
class EnsureComplaintAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Resolve the complaint from the route binding (works for {complaint} param).
        $complaint = $request->route('complaint');
        if (! $complaint instanceof Complaint) {
            // Fallback: try manual lookup if binding failed.
            $id = $request->route('complaint') ?? $request->route('id');
            if ($id) {
                $complaint = Complaint::find($id);
            }
        }

        if (! $complaint) {
            return response()->json(['message' => 'Complaint not found.'], 404);
        }

        $slug = $user->role?->slug;

        // Admins and sub-admins can access any complaint.
        if (in_array($slug, ['admin', 'sub_admin'], true)) {
            return $next($request);
        }

        // Complaint officers can access ONLY complaints currently assigned to them.
        if ($slug === 'complaint_officer') {
            $isAssigned = ComplaintAssignment::where('complaint_id', $complaint->id)
                ->where('assigned_to', $user->id)
                ->where('is_current', true)
                ->exists();

            if ($isAssigned) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Forbidden — you do not have access to this complaint.',
            ], 403);
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
