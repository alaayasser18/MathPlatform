<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMemoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('features') && is_string($this->input('features'))) {
            $decoded = json_decode($this->input('features'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->merge(['features' => $decoded]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'grade_id' => [
                'sometimes',
                'integer',
                'exists:grades,id',
            ],

            'title' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'pdf_file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:102400',
            ],

            'file_url' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'cover_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:10240',
            ],

            'file_size' => [
                'nullable',
                'string',
                'max:50',
            ],

            'pages' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],

            'year' => [
                'nullable',
                'string',
                'max:20',
            ],

            'cover_image_url' => [
                'nullable',
                'string',
                'max:2048',
            ],

            'features' => [
                'nullable',
                'array',
            ],

            'features.*' => [
                'string',
                'max:255',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_id.exists' => 'The selected grade does not exist.',
            'title.max' => 'Memo title must not exceed 255 characters.',
            'pdf_file.file' => 'Uploaded memo must be a file.',
            'pdf_file.mimes' => 'The memo file must be a PDF document.',
            'pdf_file.max' => 'The memo file must not exceed 100 megabytes.',
            'cover_image.image' => 'Cover must be an image file.',
            'cover_image.max' => 'Cover image must not exceed 10 megabytes.',
            'pages.integer' => 'Pages must be a whole number.',
            'pages.min' => 'Pages must be at least 1.',
            'features.array' => 'Features must be a list.',
        ];
    }
}
