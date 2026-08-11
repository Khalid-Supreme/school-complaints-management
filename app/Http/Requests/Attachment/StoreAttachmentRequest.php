<?php

namespace App\Http\Requests\Attachment;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization enforced by route middleware + Gate.
    }

    /**
     * Whitelist-based upload policy: allowed extensions and detected MIME types
     * only, size cap enforced, and images must decode to sane dimensions.
     * Executables, scripts and archives are rejected by the allowlist.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'attachment' => ValidationRules::attachment(),
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.required' => 'Please select a file to upload.',
            'attachment.file' => 'The uploaded value must be a file.',
            'attachment.max' => 'The file must not be larger than 10 MB.',
            'attachment.mimetypes' => 'The file type is not allowed. Only images, PDF, Word documents and text files are supported.',
            'attachment.extensions' => 'The file extension is not allowed. Only images, PDF, Word documents and text files are supported.',
        ];
    }
}
