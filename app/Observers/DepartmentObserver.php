<?php

namespace App\Observers;

use App\Enums\AuditAction;
use App\Models\Department;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Auth;

class DepartmentObserver
{
    protected AuditLogger $auditLogger;

    public function __construct(AuditLogger $auditLogger)
    {
        $this->auditLogger = $auditLogger;
    }

    /**
     * Handle the Department "created" event.
     */
    public function created(Department $department): void
    {
        $this->log(AuditAction::DepartmentCreated, $department);
    }

    /**
     * Handle the Department "updated" event.
     */
    public function updated(Department $department): void
    {
        $this->log(AuditAction::DepartmentUpdated, $department);
    }

    /**
     * Handle the Department "deleted" event.
     */
    public function deleted(Department $department): void
    {
        $this->log(AuditAction::DepartmentDeleted, $department);
    }

    protected function log(AuditAction $action, Department $department): void
    {
        $this->auditLogger->log($action, Auth::user(), $department, null, [
            'department_name' => $department->name,
        ]);
    }
}
