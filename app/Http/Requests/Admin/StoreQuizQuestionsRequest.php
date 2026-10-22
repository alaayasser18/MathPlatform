<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('questions') && $this->has('question_text')) {
            $this->merge([
                'questions' => [
                    [
                        'question_text' => $this->input('question_text'),
                        'points' => $this->input('points', 1),
                        'sort_order' => $this->input('sort_order'),
                        'options' => $this->input('options', []),
                    ],
                ],
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_percentage' => ['nullable', 'integer', 'min:1', 'max:100'],

            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question_text' => ['required', 'string'],
            'questions.*.points' => ['required', 'integer', 'min:1'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*.option_text' => ['required', 'string'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
            'questions.*.options.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'questions.required' => 'At least one question is required.',
            'questions.array' => 'Questions must be an array.',
            'questions.min' => 'At least one question is required.',
            'questions.*.question_text.required' => 'The question text is required.',
            'questions.*.points.required' => 'Question points are required.',
            'questions.*.points.min' => 'Question points must be at least 1.',
            'questions.*.options.required' => 'Each question must have options.',
            'questions.*.options.min' => 'Each question must have at least 2 options.',
            'questions.*.options.*.option_text.required' => 'Option text is required.',
            'questions.*.options.*.is_correct.required' => 'Option correctness (is_correct) is required.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $questions = $this->input('questions');
            if (! is_array($questions)) {
                return;
            }

            foreach ($questions as $qIndex => $qData) {
                if (! isset($qData['options']) || ! is_array($qData['options'])) {
                    continue;
                }

                $correctCount = 0;
                foreach ($qData['options'] as $option) {
                    if (isset($option['is_correct']) && filter_var($option['is_correct'], FILTER_VALIDATE_BOOLEAN)) {
                        $correctCount++;
                    }
                }

                if ($correctCount === 0) {
                    $validator->errors()->add(
                        "questions.{$qIndex}.options",
                        'Question '.($qIndex + 1).' must have at least one correct option.'
                    );
                } elseif ($correctCount > 1) {
                    $validator->errors()->add(
                        "questions.{$qIndex}.options",
                        'Question '.($qIndex + 1).' can only have one correct option.'
                    );
                }
            }
        });
    }
}
