<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\User;
use App\Repositories\ComplaintRepository;
use App\Support\InputSanitizer;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ComplaintService
{
    protected ComplaintRepository $repository;

    protected AesEncryptionService $encryption;

    protected ComplaintWorkflowService $workflow;

    protected AuditLogger $auditLogger;

    public function __construct(
        ComplaintRepository $repository,
        AesEncryptionService $encryption,
        ComplaintWorkflowService $workflow,
        AuditLogger $auditLogger
    ) {
        $this->repository = $repository;
        $this->encryption = $encryption;
        $this->workflow = $workflow;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Submit a new complaint.
     */
    public function submitComplaint(array $data, int $complainantId): Complaint
    {
        // Enforce a maximum of two complaints per user per day.
        $dailyLimit = (int) config('complaints.daily_limit', 2);
        $todayCount = Complaint::where('complainant_id', $complainantId)
            ->whereDate('submitted_at', now()->toDateString())
            ->count();

        if ($todayCount >= $dailyLimit) {
            throw ValidationException::withMessages([
                'category_id' => "Daily complaint limit reached. You can submit a maximum of {$dailyLimit} complaints per day.",
            ]);
        }

        $category = ComplaintCategory::find($data['category_id']);

        // Generate a unique reference number
        $referenceNo = strtoupper(Str::random(10));

        $complaintData = [
            'reference_no' => $referenceNo,
            'complainant_id' => $complainantId,
            'category_id' => $category ? $category->id : null,
            'title_encrypted' => $this->encryption->encrypt(InputSanitizer::clean($data['title']) ?? ''),
            'description_encrypted' => $this->encryption->encrypt(InputSanitizer::clean($data['description']) ?? ''),
            'priority' => $category ? $category->default_priority : 'medium',
            'status' => 'submitted',
            'source' => 'web',
            'submitted_at' => now(),
        ];

        $complaint = $this->repository->create($complaintData);

        $this->auditLogger->log(
            AuditAction::ComplaintCreated,
            auth()->user() ?? User::find($complainantId),
            $complaint->fresh(),
            null,
            [
                'complaint_reference' => $referenceNo,
                'category_id' => $complaintData['category_id'],
                'priority' => $complaintData['priority'],
                'source' => $complaintData['source'],
            ]
        );

        return $complaint;
    }

    /**
     * Decrypt the sensitive fields of a complaint for display.
     */
    public function decryptComplaint(Complaint $complaint): array
    {
        $complaintArray = $complaint->toArray();
        $complaintArray['category'] = $complaint->category->name ?? 'Uncategorized';
        $complaintArray['title'] = $this->encryption->decrypt($complaint->title_encrypted);
        $complaintArray['description'] = $this->encryption->decrypt($complaint->description_encrypted);
        $complainantDetails = null;
        if ($complaint->complainant) {
            $complainantDetails = [
                'institution_id' => $complaint->complainant->institution_id,
                'full_name' => $complaint->complainant->full_name,
                'email' => $complaint->complainant->email,
                'department' => $complaint->complainant->department?->name,
            ];
        }
        $complaintArray['complainant'] = $complainantDetails;
        $complaintArray['assigned_at'] = $complaint->assigned_at ?? null;
        $complaintArray['resolved_at'] = $complaint->resolved_at ?? null;

        // Remove encrypted payloads from the returned array for safety
        unset($complaintArray['title_encrypted']);
        unset($complaintArray['description_encrypted']);

        return $complaintArray;
    }

    /**
     * Get decrypted complaints for a user.
     */
    public function getComplainantComplaints(int $userId): array
    {
        $paginator = $this->repository->getByComplainant($userId);

        $decryptedItems = collect($paginator->items())->map(function ($complaint) {
            return $this->decryptComplaint($complaint);
        });

        return [
            'data' => $decryptedItems,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
