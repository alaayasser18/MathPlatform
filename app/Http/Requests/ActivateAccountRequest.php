<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivateAccountRequest extends FormRequest
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
                'max:20',
                'unique:students,phone',
            ],

            'parent_phone' => [
                'required',
                'string',
                'max:20',
            ],

            'grade_id' => [
                'required',
                'integer',
                'exists:grades,id',
            ],
        ];
    }
}