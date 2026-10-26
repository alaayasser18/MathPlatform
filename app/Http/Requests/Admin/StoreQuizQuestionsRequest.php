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
        $rawQuestions = $this->input('questions');

        // Handle single question payload at root level
        if (! $rawQuestions && ($this->has('question_text') || $this->has('question'))) {
            $rawQuestions = [
                [
                    'question_text' => $this->input('question_text') ?? $this->input('question'),
                    'points' => $this->input('points', 1),
                    'sort_order' => $this->input('sort_order', 1),
                    'options' => $this->input('options', []),
                ],
            ];
        }

        // If questions is present, normalize every question and its options
        if (is_array($rawQuestions)) {
            $normalizedQuestions = [];

            foreach ($rawQuestions as $index => $q) {
                if (! is_array($q)) {
                    continue;
                }

                $qText = $q['question_text'] ?? $q['question'] ?? $q['title'] ?? null;
                $points = isset($q['points']) ? (int) $q['points'] : 1;
                $sortOrder = isset($q['sort_order']) ? (int) $q['sort_order'] : ($index + 1);

                $rawOptions = $q['options'] ?? [];
                $normalizedOptions = [];

                if (is_array($rawOptions)) {
                    $isCorrectExplicitlyProvided = false;
                    foreach ($rawOptions as $opt) {
                        if (is_array($opt) && array_key_exists('is_correct', $opt)) {
                            $isCorrectExplicitlyProvided = true;
                            break;
                        }
                    }

                    foreach ($rawOptions as $optIndex => $opt) {
                        if (is_string($opt)) {
                            $optText = $opt;
                            $isCorrect = ($optIndex === 0);
                        } elseif (is_array($opt)) {
                            $optText = $opt['option_text'] ?? $opt['option'] ?? $opt['text'] ?? $opt['title'] ?? '';
                            if (array_key_exists('is_correct', $opt)) {
                                $isCorrect = filter_var($opt['is_correct'], FILTER_VALIDATE_BOOLEAN);
                            } else {
                                $isCorrect = (! $isCorrectExplicitlyProvided && $optIndex === 0);
                            }
                        } else {
                            continue;
                        }

                        $normalizedOptions[] = [
                            'option_text' => (string) $optText,
                            'is_correct' => $isCorrect,
                            'sort_order' => $opt['sort_order'] ?? ($optIndex + 1),
                        ];
                    }
                }

                $normalizedQuestions[] = [
                    'question_text' => $qText,
                    'points' => max(1, $points),
                    'sort_order' => $sortOrder,
                    'options' => $normalizedOptions,
                ];
            }

            $this->merge(['questions' => $normalizedQuestions]);
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
            'questions.*.points' => ['nullable', 'integer', 'min:1'],
            'questions.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*.option_text' => ['required_with:questions.*.options', 'string'],
            'questions.*.options.*.is_correct' => ['nullable', 'boolean'],
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
            'questions.*.options.*.option_text.required' => 'Option text is required.',
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
                if (! isset($qData['options']) || ! is_array($qData['options']) || empty($qData['options'])) {
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
