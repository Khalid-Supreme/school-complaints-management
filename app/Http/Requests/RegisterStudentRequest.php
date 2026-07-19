<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

class RegisterStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'department' => ['required', 'integer', 'exists:departments,id'],
            'gender' => ['required', 'string', 'in:male,female'],
            'password' => ['required', 'string', 'min:8'],
            'confirm_password' => ['required', 'same:password'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $dept = Department::find($this->department);
                if ($dept && $dept->type !== 'academic') {
                    $validator->errors()->add('department', 'Students can only select an academic department.');
                }
            },
        ];
    }
}
