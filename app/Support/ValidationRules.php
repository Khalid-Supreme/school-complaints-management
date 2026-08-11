<?php

namespace App\Support;

use App\Rules\SafeImage;
use App\Services\ComplaintWorkflowService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

/**
 * Central, reusable validation rules shared across the application's
 * Form Request classes so that every endpoint validates consistently.
 */
class ValidationRules
{
    public const GENDERS = ['male', 'female'];

    public const TITLES = ['Mr.', 'Mrs.', 'Miss', 'Dr.', 'Prof.'];

    public const CREATABLE_ROLE_SLUGS = ['student', 'staff', 'complaint_officer'];

    public const DEPARTMENT_TYPES = ['academic', 'non-academic'];

    public const USER_STATUSES = ['active', 'inactive'];

    protected const NAME_PATTERN = '/^[\p{L}\p{M}\'’\- ]+$/u';

    public static function firstName(): array
    {
        return ['required', ...self::nameRules()];
    }

    public static function lastName(): array
    {
        return ['required', ...self::nameRules()];
    }

    public static function optionalFirstName(): array
    {
        return ['sometimes', ...self::nameRules()];
    }

    public static function optionalLastName(): array
    {
        return ['sometimes', ...self::nameRules()];
    }

    public static function email(): array
    {
        return ['required', 'string', 'email', 'encoding:UTF-8', 'max:150'];
    }

    public static function uniqueEmail(int|string|null $ignoreId = null): array
    {
        return [...self::email(), self::uniqueRule($ignoreId)];
    }

    public static function optionalEmail(int|string|null $ignoreId = null): array
    {
        return ['sometimes', 'string', 'email', 'encoding:UTF-8', 'max:150', self::uniqueRule($ignoreId)];
    }

    public static function requiredPassword(): array
    {
        return ['required', ...self::password()];
    }

    public static function confirmedPassword(): array
    {
        return ['required', 'confirmed', ...self::password()];
    }

    public static function optionalPassword(): array
    {
        return ['sometimes', 'nullable', 'string', ...self::password()];
    }

    public static function departmentId(): array
    {
        return ['required', 'integer', 'exists:departments,id'];
    }

    public static function optionalDepartmentId(): array
    {
        return ['sometimes', 'integer', 'exists:departments,id'];
    }

    public static function gender(): array
    {
        return ['required', 'string', Rule::in(self::GENDERS)];
    }

    public static function optionalGender(): array
    {
        return ['sometimes', 'string', Rule::in(self::GENDERS)];
    }

    public static function title(): array
    {
        return ['required', 'string', Rule::in(self::TITLES)];
    }

    public static function optionalTitle(): array
    {
        return ['sometimes', 'string', Rule::in(self::TITLES)];
    }

    public static function creatableRoleSlug(): array
    {
        return ['required', 'string', Rule::in(self::CREATABLE_ROLE_SLUGS)];
    }

    public static function complaintStatus(): array
    {
        return ['required', 'string', Rule::in(ComplaintWorkflowService::STATUSES)];
    }

    public static function departmentTypeFilter(): array
    {
        return ['nullable', 'string', Rule::in(self::DEPARTMENT_TYPES)];
    }

    public static function userStatusFilter(): array
    {
        return ['nullable', 'string', Rule::in(self::USER_STATUSES)];
    }

    public static function search(): array
    {
        return ['nullable', 'string', 'encoding:UTF-8', 'max:100'];
    }

    public static function categoryId(): array
    {
        return ['required', 'integer', 'exists:complaint_categories,id'];
    }

    public static function optionalCategoryId(): array
    {
        return ['sometimes', 'integer', 'exists:complaint_categories,id'];
    }

    public static function complaintTitle(): array
    {
        return self::utf8String(max: 255, min: 3);
    }

    public static function complaintDescription(): array
    {
        return self::utf8String(max: 5000, min: 10);
    }

    public static function chatMessage(): array
    {
        return self::utf8String(max: 5000);
    }

    public static function assignmentNote(): array
    {
        return self::utf8String(max: 1000, required: false, nullable: true);
    }

    /**
     * Central attachment upload policy.
     *
     * Enforces the file size cap, the extension and detected-MIME allowlists
     * (executables, scripts and archives are excluded) and pixel caps for
     * images. All values come from config/uploads.php.
     *
     * @return array<int, mixed>
     */
    public static function attachment(): array
    {
        return [
            'required',
            'file',
            'max:'.config('uploads.attachments.max_size_kb'),
            'mimetypes:'.implode(',', config('uploads.attachments.allowed_mimes')),
            'extensions:'.implode(',', config('uploads.attachments.allowed_extensions')),
            new SafeImage,
        ];
    }

    public static function appName(): array
    {
        return self::utf8String(max: 255, required: false, nullable: true);
    }

    public static function contactPhone(): array
    {
        return self::utf8String(max: 50, required: false, nullable: true);
    }

    public static function addressText(): array
    {
        return self::utf8String(max: 500, required: false, nullable: true);
    }

    public static function contactEmail(): array
    {
        return ['sometimes', 'nullable', 'string', 'email', 'encoding:UTF-8', 'max:255'];
    }

    protected static function nameRules(): array
    {
        return ['string', 'min:2', 'max:150', 'encoding:UTF-8', 'regex:'.self::NAME_PATTERN];
    }

    protected static function utf8String(int $max, bool $required = true, bool $nullable = false, ?int $min = null): array
    {
        $rules = [$required ? 'required' : 'sometimes'];

        if ($nullable) {
            $rules[] = 'nullable';
        }

        $rules[] = 'string';
        $rules[] = 'encoding:UTF-8';

        if ($min !== null) {
            $rules[] = "min:{$min}";
        }

        $rules[] = "max:{$max}";

        return $rules;
    }

    protected static function password(): array
    {
        return [Password::min(8)->letters()->numbers()];
    }

    protected static function uniqueRule(int|string|null $ignoreId): Unique
    {
        return $ignoreId
            ? Rule::unique('users', 'email')->ignore($ignoreId)
            : Rule::unique('users', 'email');
    }
}
