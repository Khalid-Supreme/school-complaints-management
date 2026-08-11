<?php

namespace App\Http\Requests\Complaint;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateComplaintStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization enforced by route middleware (role + complaint.access).
    }

    /**
     * Whitelist aligned with ComplaintWorkflowService::STATUSES.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => ValidationRules::complaintStatus(),
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Please select a status.',
            'status.in' => 'The selected status is not a valid complaint status.',
        ];
    }
}
