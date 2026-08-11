<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization enforced in the controller (canAssignRole).
    }

    /**
     * Whitelist the assignable roles. 'admin' can never be assigned via this endpoint.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:staff,complaint_officer,sub_admin'],
        ];
    }
}
