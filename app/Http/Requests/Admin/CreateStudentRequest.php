<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
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
                'unique:students,phone',
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

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'code_expires_at' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }
}