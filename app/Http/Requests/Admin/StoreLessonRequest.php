<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('quiz') && is_array($this->input('quiz'))) {
            $quiz = $this->input('quiz');
            $this->merge([
                'quiz_title' => $this->input('quiz_title') ?? ($quiz['title'] ?? null),
                'quiz_description' => $this->input('quiz_description') ?? ($quiz['description'] ?? null),
                'duration_minutes' => $this->input('duration_minutes') ?? ($quiz['duration_minutes'] ?? null),
                'passing_percentage' => $this->input('passing_percentage') ?? ($quiz['passing_percentage'] ?? null),
                'questions' => $this->input('questions') ?? ($quiz['questions'] ?? null),
            ]);
        }

        $rawQuestions = $this->input('questions');

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

            'quiz_title' => ['nullable', 'string', 'max:255'],
            'quiz_description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_percentage' => ['nullable', 'integer', 'min:1', 'max:100'],

            'questions' => ['nullable', 'array'],
            'questions.*.question_text' => ['required_with:questions', 'string'],
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
            'grade_id.required' => 'Grade is required.',
            'grade_id.exists' => 'The selected grade does not exist.',

            'section_name.required' => 'Section name is required.',

            'title.required' => 'Lesson title is required.',

            'video_url.required' => 'YouTube video URL is required.',
            'video_url.url' => 'Please provide a valid URL.',
            'video_url.regex' => 'The video URL must be a YouTube URL.',

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

                if ($correctCount > 1) {
                    $validator->errors()->add(
                        "questions.{$qIndex}.options",
                        'Question '.($qIndex + 1).' can only have one correct option.'
                    );
                }
            }
        });
    }
}