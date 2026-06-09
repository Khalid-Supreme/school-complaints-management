<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'complaint_id',
    'assigned_to',
    'assigned_by',
    'assignment_note_encrypted',
    'is_current',
    'assigned_at',
    'released_at',
])]
class ComplaintAssignment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
            'is_current' => 'boolean',
        ];
    }

    /**
     * Get the complaint this assignment belongs to.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Get the staff member this complaint was assigned to.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the admin/user who made the assignment.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
