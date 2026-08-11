<?php

namespace App\Http\Requests\Settings;

use App\Support\ValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Role-based access enforced by route middleware.
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All free-text settings are UTF-8 validated; empty strings were already
     * normalized to null in prepareForValidation() so optional columns are
     * cleared instead of storing a blank string.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'app_name' => ValidationRules::appName(),
            'contact_email' => ValidationRules::contactEmail(),
            'contact_phone' => ValidationRules::contactPhone(),
            'address' => ValidationRules::addressText(),
            'social_links' => ['sometimes', 'nullable', 'array'],
            'social_links.twitter' => ['nullable', 'string', 'encoding:UTF-8', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'encoding:UTF-8', 'max:255'],
            'social_links.linkedin' => ['nullable', 'string', 'encoding:UTF-8', 'max:255'],
            'meta' => ['sometimes', 'nullable', 'array', 'max:10'],
            'meta.*' => ['nullable', 'string', 'encoding:UTF-8', 'max:500'],
        ];
    }

    /**
     * Reject unknown social link keys so arbitrary data cannot be stored.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $allowedKeys = ['twitter', 'facebook', 'linkedin'];
            $social = $this->input('social_links');

            if (! is_array($social)) {
                return;
            }

            foreach (array_keys($social) as $key) {
                if (! in_array($key, $allowedKeys, true)) {
                    $validator->errors()->add('social_links', "Unsupported social link key: {$key}.");
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        // Blank strings for optional settings become null so the columns are
        // cleared rather than holding an empty value. app_name is NOT null.
        foreach (['contact_email', 'contact_phone', 'address'] as $field) {
            $all = $this->all();
            if (array_key_exists($field, $all) && trim((string) $all[$field]) === '') {
                $this->merge([$field => null]);
            }
        }
    }
}
