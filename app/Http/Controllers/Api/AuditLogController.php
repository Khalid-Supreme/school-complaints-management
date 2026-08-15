<?php

namespace App\Http\Controllers\Api;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Audit\IndexAuditLogsRequest;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use LogicException;

class AuditLogController extends Controller
{
    /**
     * List audit records (admin-only).
     */
    public function index(IndexAuditLogsRequest $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with(['user:id,first_name,last_name,title,email,role_id', 'user.role:id,slug']);

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('ip_address')) {
            $query->where('ip_address', $request->input('ip_address'));
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        if ($request->filled('search')) {
            $needle = trim($request->input('search'));
            $query->where(function ($q) use ($needle) {
                $q->where('description', 'like', "%{$needle}%")
                    ->orWhere('metadata', 'like', "%{$needle}%")
                    ->orWhere('user_agent', 'like', "%{$needle}%");
            });
        }

        $paginator = $query->orderByDesc('created_at')->paginate($request->input('per_page', 20));

        // Resolve human-readable action labels without a DB round-trip per row.
        $paginator->getCollection()->transform(function (AuditLog $log) {
            $action = AuditAction::tryFrom($log->action);

            if (! $action) {
                throw new LogicException("Unknown audit action encountered: {$log->action}");
            }

            $log->setAttribute('action_label', $action->label());

            return $log;
        });

        return response()->json($paginator);
    }
}
