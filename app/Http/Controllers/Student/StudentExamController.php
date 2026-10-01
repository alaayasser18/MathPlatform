<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Services\LearningProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Student Exams',
    description: 'Student exam access, start and submission endpoints'
)]
class StudentExamController extends Controller
{
    public function __construct(
        private LearningProgressService $learningProgressService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/student/exams/{exam}',
        operationId: 'studentExamShow',
        summary: 'Get exam questions',
        description: 'The exam can only be accessed after all active videos in the lesson are completed.',
        tags: ['Student Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'exam',
                description: 'Exam ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Exam retrieved successfully'),
            new OA\Response(response: 403, description: 'Exam is locked'),
            new OA\Response(response: 404, description: 'Exam not found'),
        ]
    )]
    public function show(
        Request $request,
        int $exam
    ): JsonResponse {
        $student = $request->user();

        $examModel = Exam::query()
            ->where('is_active', true)
            ->with([
                'lesson.section',
                'questions' => function ($query) {
                    $query->with([
                        'options' => function ($optionQuery) {
                            $optionQuery->select([
                                'id',
                                'question_id',
                                'option_text',
                                'sort_order',
                            ]);
                        },
                    ]);
                },
            ])
            ->find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (
            !$student->grade_id ||
            !$examModel->lesson?->section ||
            $examModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $isUnlocked = $this->learningProgressService
            ->isExamUnlocked(
                $student->id,
                $subscription->id,
                $examModel->lesson
            );

        if (!$isUnlocked) {
            return response()->json([
                'success' => false,
                'message' => 'This exam is locked. Complete all lesson videos first.',
            ], 403);
        }

        $examModel->questions->each(function ($question) {
            $question->makeHidden('options.is_correct');
        });

        return response()->json([
            'success' => true,
            'message' => 'Exam retrieved successfully.',
            'data' => [
                'exam' => $examModel,
                'passing_percentage' => (int) $examModel->passing_percentage,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/student/exams/{exam}/start',
        operationId: 'studentExamStart',
        summary: 'Start an exam',
        description: 'Starts an exam after all active lesson videos have been completed.',
        tags: ['Student Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'exam',
                description: 'Exam ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Exam started successfully'),
            new OA\Response(response: 403, description: 'Exam is locked'),
            new OA\Response(response: 404, description: 'Exam not found'),
        ]
    )]
    public function start(
        Request $request,
        int $exam
    ): JsonResponse {
        $student = $request->user();

        $examModel = Exam::query()
            ->where('is_active', true)
            ->with([
                'lesson.section',
                'questions',
            ])
            ->find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (
            !$student->grade_id ||
            !$examModel->lesson?->section ||
            $examModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        if (!$this->learningProgressService->isExamUnlocked(
            $student->id,
            $subscription->id,
            $examModel->lesson
        )) {
            return response()->json([
                'success' => false,
                'message' => 'This exam is locked. Complete all lesson videos first.',
            ], 403);
        }

        $existingAttempt = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('subscription_id', $subscription->id)
            ->where('exam_id', $examModel->id)
            ->whereNull('submitted_at')
            ->latest('id')
            ->first();

        if ($existingAttempt) {
            return response()->json([
                'success' => true,
                'message' => 'Exam already started.',
                'data' => [
                    'attempt_id' => $existingAttempt->id,
                    'started_at' => $existingAttempt->started_at,
                ],
            ]);
        }

        $totalScore = (int) $examModel->questions->sum('points');

        if ($examModel->questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'This exam has no questions.',
            ], 422);
        }

        $attempt = ExamAttempt::create([
            'student_id' => $student->id,
            'exam_id' => $examModel->id,
            'subscription_id' => $subscription->id,
            'score' => 0,
            'total_score' => $totalScore,
            'percentage' => 0,
            'is_passed' => false,
            'started_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam started successfully.',
            'data' => [
                'attempt_id' => $attempt->id,
                'started_at' => $attempt->started_at,
                'total_score' => $totalScore,
                'passing_percentage' => (int) $examModel->passing_percentage,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/student/exams/{exam}/submit',
        operationId: 'studentExamSubmit',
        summary: 'Submit exam answers',
        description: 'All questions must be answered. The backend calculates the score and determines whether the student passed.',
        tags: ['Student Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'exam',
                description: 'Exam ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['attempt_id', 'answers'],
                properties: [
                    new OA\Property(
                        property: 'attempt_id',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'answers',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['question_id', 'option_id'],
                            properties: [
                                new OA\Property(
                                    property: 'question_id',
                                    type: 'integer',
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'option_id',
                                    type: 'integer',
                                    example: 3
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Exam submitted successfully'),
            new OA\Response(response: 403, description: 'Exam is locked'),
            new OA\Response(response: 404, description: 'Exam not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function submit(
        Request $request,
        int $exam
    ): JsonResponse {
        $validated = $request->validate([
            'attempt_id' => [
                'required',
                'integer',
                'exists:exam_attempts,id',
            ],

            'answers' => [
                'required',
                'array',
                'min:1',
            ],

            'answers.*.question_id' => [
                'required',
                'integer',
                'distinct',
            ],

            'answers.*.option_id' => [
                'required',
                'integer',
            ],
        ]);

        $student = $request->user();

        $examModel = Exam::query()
            ->where('is_active', true)
            ->with([
                'lesson.section',
                'questions.options',
            ])
            ->find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (
            !$student->grade_id ||
            !$examModel->lesson?->section ||
            $examModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to submit this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        if (!$this->learningProgressService->isExamUnlocked(
            $student->id,
            $subscription->id,
            $examModel->lesson
        )) {
            return response()->json([
                'success' => false,
                'message' => 'This exam is locked. Complete all lesson videos first.',
            ], 403);
        }

        $attempt = ExamAttempt::query()
            ->where('id', $validated['attempt_id'])
            ->where('student_id', $student->id)
            ->where('subscription_id', $subscription->id)
            ->where('exam_id', $examModel->id)
            ->first();

        if (!$attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Exam attempt not found.',
            ], 404);
        }

        if ($attempt->submitted_at) {
            return response()->json([
                'success' => false,
                'message' => 'This exam attempt has already been submitted.',
            ], 422);
        }

        $questions = $examModel->questions->keyBy('id');

        /*
        |--------------------------------------------------------------------------
        | Make sure every question is answered
        |--------------------------------------------------------------------------
        */
        $submittedQuestionIds = collect($validated['answers'])
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        $examQuestionIds = $questions
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values();

        if (
            $submittedQuestionIds->count() !== $examQuestionIds->count() ||
            $submittedQuestionIds->toArray() !== $examQuestionIds->toArray()
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You must answer all exam questions before submitting.',
            ], 422);
        }

        /*
        |--------------------------------------------------------------------------
        | Validate every selected option belongs to its question
        |--------------------------------------------------------------------------
        */
        foreach ($validated['answers'] as $answer) {
            $questionId = (int) $answer['question_id'];
            $optionId = (int) $answer['option_id'];

            $question = $questions->get($questionId);

            if (!$question) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid question submitted.',
                ], 422);
            }

            $optionBelongsToQuestion = $question->options
                ->contains('id', $optionId);

            if (!$optionBelongsToQuestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected option does not belong to the selected question.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Calculate score
        |--------------------------------------------------------------------------
        */
        $answers = collect($validated['answers'])
            ->keyBy(fn ($answer) => (int) $answer['question_id']);

        $score = 0;
        $totalScore = 0;

        foreach ($questions as $question) {
            $totalScore += (int) $question->points;

            $selectedOptionId = (int) $answers
                ->get($question->id)['option_id'];

            $selectedOption = $question->options
                ->firstWhere('id', $selectedOptionId);

            if ($selectedOption && $selectedOption->is_correct) {
                $score += (int) $question->points;
            }
        }

        $percentage = $totalScore > 0
            ? round(($score / $totalScore) * 100, 2)
            : 0;

        $isPassed = $percentage >= (int) $examModel->passing_percentage;

        DB::transaction(function () use (
            $attempt,
            $score,
            $totalScore,
            $percentage,
            $isPassed
        ) {
            $attempt->update([
                'score' => $score,
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'is_passed' => $isPassed,
                'submitted_at' => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => $isPassed
                ? 'Exam submitted successfully. You passed the exam.'
                : 'Exam submitted successfully. You did not pass the exam.',
            'data' => [
                'attempt_id' => $attempt->id,
                'score' => $score,
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'passing_percentage' => (int) $examModel->passing_percentage,
                'is_passed' => $isPassed,
                'next_lesson_unlocked' => $isPassed,
            ],
        ]);
    }
}