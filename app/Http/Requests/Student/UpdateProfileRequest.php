<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'parent_phone' => ['sometimes', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.string' => 'First name must be a valid string.',
            'first_name.max' => 'First name may not be greater than 100 characters.',
            'last_name.string' => 'Last name must be a valid string.',
            'last_name.max' => 'Last name may not be greater than 100 characters.',
            'parent_phone.string' => 'Parent phone must be a valid string.',
            'parent_phone.max' => 'Parent phone may not be greater than 30 characters.',
        ];
    }
}
