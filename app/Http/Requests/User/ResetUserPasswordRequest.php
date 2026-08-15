<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization enforced in the controller (canManageUsers).
    }

    /**
     * The temporary password is generated server-side and emailed to the
     * account owner; the administrator never supplies or sees it.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [];
    }
}
