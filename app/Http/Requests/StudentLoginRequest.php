<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StudentLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_code' => [
                'required',
                'string',
                'max:50',
            ],

            'device_identifier' => [
                'required',
                'uuid',
            ],
        ];
    }
}