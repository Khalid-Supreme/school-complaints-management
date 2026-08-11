<?php

namespace App\Http\Requests;

use App\Models\Department;

class RegisterStudentRequest extends RegisterUserRequest
{
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
