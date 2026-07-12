<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Repositories\ComplaintRepository;
use Illuminate\Support\Str;

class ComplaintService
{
    protected ComplaintRepository $repository;
    protected AesEncryptionService $encryption;
    protected ComplaintWorkflowService $workflow;

    public function __construct(
        ComplaintRepository $repository,
        AesEncryptionService $encryption,
        ComplaintWorkflowService $workflow
    ) {
        $this->repository = $repository;
        $this->encryption = $encryption;
        $this->workflow = $workflow;
    }

    /**
     * Submit a new complaint.
     *
     * @param array $data
     * @param int $complainantId
     * @return Complaint
     */
    public function submitComplaint(array $data, int $complainantId): Complaint
    {
        $category = ComplaintCategory::find($data['category_id']);
        
        // Generate a unique reference number
        $referenceNo = strtoupper(Str::random(10));

        $complaintData = [
            'reference_no' => $referenceNo,
            'complainant_id' => $complainantId,
            'category_id' => $category ? $category->id : null,
            'title_encrypted' => $this->encryption->encrypt($data['title']),
            'description_encrypted' => $this->encryption->encrypt($data['description']),
            'priority' => $category ? $category->default_priority : 'medium',
            'status' => 'submitted',
            'source' => 'web',
            'submitted_at' => now(),
        ];

        return $this->repository->create($complaintData);
    }

    /**
     * Decrypt the sensitive fields of a complaint for display.
     * 
     * @param Complaint $complaint
     * @return array
     */
    public function decryptComplaint(Complaint $complaint): array
    {
        $complaintArray = $complaint->toArray();
        $complaintArray['category'] = $complaint->category->name??'Uncategorized';
        $complaintArray['title'] = $this->encryption->decrypt($complaint->title_encrypted);
        $complaintArray['description'] = $this->encryption->decrypt($complaint->description_encrypted);
        $complainantDetails = null; 
        if ($complaint->complainant) {
            $complainantDetails = [
            'institution_id' => $complaint->complainant->institution_id,
            'name' => $complaint->complainant->name,
            'email' => $complaint->complainant->email,
            'department' => $complaint->complainant->department,
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
     *
     * @param int $userId
     * @return array
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
            ]
        ];
    }
}
