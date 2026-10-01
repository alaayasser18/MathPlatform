<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grade_id' => [
                'required',
                'integer',
                'exists:grades,id',
            ],

            'section_name' => [
                'required',
                'string',
                'max:255',
            ],

            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'video_url' => [
                'required',
                'url',
                'regex:/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\//i',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_id.required' => 'Grade is required.',
            'grade_id.exists' => 'The selected grade does not exist.',

            'section_name.required' => 'Section name is required.',

            'title.required' => 'Lesson title is required.',

            'video_url.required' => 'YouTube video URL is required.',
            'video_url.url' => 'Please provide a valid URL.',
            'video_url.regex' => 'The video URL must be a YouTube URL.',
        ];
    }
}