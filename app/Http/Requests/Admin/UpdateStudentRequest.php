<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        $studentId =
            $this->route('student')?->id
            ?? $this->route('student');

        return [
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],

            'last_name' => [
                'required',
                'string',
                'max:100',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'students',
                    'phone'
                )->ignore($studentId),
            ],

            'parent_phone' => [
                'required',
                'string',
                'max:30',
            ],

            'grade_id' => [
                'required',
                'integer',
                'exists:grades,id',
            ],
        ];
    }
}