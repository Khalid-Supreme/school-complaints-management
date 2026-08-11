<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\ComplaintCategory;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class ComplaintCategoryObserver
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the ComplaintCategory "created" event.
     */
    public function created(ComplaintCategory $category): void
    {
        $this->log(AuditAction::CategoryCreated, $category);
    }

    /**
     * Handle the ComplaintCategory "updated" event.
     */
    public function updated(ComplaintCategory $category): void
    {
        $this->log(AuditAction::CategoryUpdated, $category);
    }

    /**
     * Handle the ComplaintCategory "deleted" event.
     */
    public function deleted(ComplaintCategory $category): void
    {
        $this->log(AuditAction::CategoryDeleted, $category);
    }

    protected function log(AuditAction $action, ComplaintCategory $category): void
    {
        $this->auditLogger->log($action, Auth::user(), $category, null, [
            'category_name' => $category->name,
        ]);
    }
}
