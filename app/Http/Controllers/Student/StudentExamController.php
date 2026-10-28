<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Student;
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
    ) {}

    /**
     * List all active exams for the student's grade with progress and unlock status.
     */
    #[OA\Get(
        path: '/api/v1/student/exams',
        operationId: 'studentExamsIndex',
        summary: 'Get all exams for student\'s grade',
        tags: ['Student Exams'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Exams retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();
        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => true,
                'message' => 'No grade assigned to this student.',
                'data' => [
                    'exams' => [],
                ],
            ]);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => true,
                'message' => 'No active subscription found.',
                'data' => [
                    'exams' => [],
                ],
            ]);
        }

        $exams = Exam::query()
            ->where('is_active', true)
            ->where(function ($query) use ($student) {
                $query->whereHas('lesson.section', function ($q) use ($student) {
                    $q->where('grade_id', $student->grade_id)
                        ->where('is_active', true);
                })->orWhere(function ($q) use ($student) {
                    $q->whereNull('lesson_id')
                        ->where(function ($gradeQuery) use ($student) {
                            $gradeQuery->where('grade_id', $student->grade_id)
                                ->orWhereNull('grade_id');
                        });
                });
            })
            ->with([
                'grade:id,name',
                'lesson.section:id,grade_id,name,sort_order',
            ])
            ->withCount('questions')
            ->orderBy('id')
            ->get();

        $studentAttempts = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('subscription_id', $subscription->id)
            ->whereNotNull('submitted_at')
            ->get()
            ->groupBy('exam_id');

        $examList = $exams->map(function (Exam $exam) use ($student, $subscription, $studentAttempts) {
            $isUnlocked = $this->getExamLockMessage($student, $subscription->id, $exam) === null;

            $attempts = $studentAttempts->get($exam->id, collect());
            $bestAttempt = $attempts->sortByDesc('score')->first();
            $latestAttempt = $attempts->sortByDesc('id')->first();
            $hasPassed = $attempts->where('is_passed', true)->isNotEmpty();

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'grade_id' => $exam->grade_id,
                'lesson_id' => $exam->lesson_id,
                'is_standalone' => $exam->lesson_id === null,
                'lesson_title' => $exam->lesson?->title,
                'section_name' => $exam->lesson?->section?->name,
                'duration_minutes' => $exam->duration_minutes,
                'passing_percentage' => $exam->passing_percentage,
                'questions_count' => $exam->questions_count,
                'is_unlocked' => $isUnlocked,
                'is_passed' => $hasPassed,
                'attempts_count' => $attempts->count(),
                'best_score' => $bestAttempt ? [
                    'score' => $bestAttempt->score,
                    'total_score' => $bestAttempt->total_score,
                    'percentage' => $bestAttempt->percentage,
                    'is_passed' => $bestAttempt->is_passed,
                ] : null,
                'latest_attempt' => $latestAttempt ? [
                    'id' => $latestAttempt->id,
                    'score' => $latestAttempt->score,
                    'total_score' => $latestAttempt->total_score,
                    'percentage' => $latestAttempt->percentage,
                    'is_passed' => $latestAttempt->is_passed,
                    'submitted_at' => $latestAttempt->submitted_at?->toIso8601String(),
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Exams retrieved successfully.',
            'data' => [
                'grade' => [
                    'id' => $student->grade->id,
                    'name' => $student->grade->name,
                ],
                'exams' => $examList,
            ],
        ]);
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
                'grade:id,name',
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

        if (! $examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (! $this->canStudentAccessExam($student, $examModel)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $lockMessage = $this->getExamLockMessage($student, $subscription->id, $examModel);

        if ($lockMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $lockMessage,
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
                'grade:id,name',
                'lesson.section',
                'questions',
            ])
            ->find($exam);

        if (! $examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (! $this->canStudentAccessExam($student, $examModel)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $lockMessage = $this->getExamLockMessage($student, $subscription->id, $examModel);

        if ($lockMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $lockMessage,
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
        description: 'Submits exam answers. Unanswered questions are counted as incorrect without saving an answer record.',
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
                required: ['attempt_id'],
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
                'nullable',
                'array',
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

        $submittedAnswers = $validated['answers'] ?? [];

        $student = $request->user();

        $examModel = Exam::query()
            ->where('is_active', true)
            ->with([
                'grade:id,name',
                'lesson.section',
                'questions.options',
            ])
            ->find($exam);

        if (! $examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (! $this->canStudentAccessExam($student, $examModel)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to submit this exam.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $lockMessage = $this->getExamLockMessage($student, $subscription->id, $examModel);

        if ($lockMessage !== null) {
            return response()->json([
                'success' => false,
                'message' => $lockMessage,
            ], 403);
        }

        $attempt = ExamAttempt::query()
            ->where('id', $validated['attempt_id'])
            ->where('student_id', $student->id)
            ->where('subscription_id', $subscription->id)
            ->where('exam_id', $examModel->id)
            ->first();

        if (! $attempt) {
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
        | Make sure submitted questions belong to this exam
        |--------------------------------------------------------------------------
        */
        $submittedQuestionIds = collect($submittedAnswers)
            ->pluck('question_id')
            ->map(fn ($id) => (int) $id);

        foreach ($submittedQuestionIds as $qId) {
            if (! $questions->has($qId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid question submitted for this exam.',
                ], 422);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate every selected option belongs to its question
        |--------------------------------------------------------------------------
        */
        foreach ($submittedAnswers as $answer) {
            $questionId = (int) $answer['question_id'];
            $optionId = (int) $answer['option_id'];

            $question = $questions->get($questionId);

            $optionBelongsToQuestion = $question && $question->options
                ->contains('id', $optionId);

            if (! $optionBelongsToQuestion) {
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
        $answers = collect($submittedAnswers)
            ->keyBy(fn ($answer) => (int) $answer['question_id']);

        $score = 0;
        $totalScore = 0;
        $answersToSave = [];

        foreach ($questions as $question) {
            $totalScore += (int) $question->points;

            $answer = $answers->get($question->id);

            if ($answer !== null) {
                $selectedOptionId = (int) $answer['option_id'];

                $selectedOption = $question->options
                    ->firstWhere('id', $selectedOptionId);

                $isCorrect = $selectedOption && (bool) $selectedOption->is_correct;

                if ($isCorrect) {
                    $score += (int) $question->points;
                }

                $answersToSave[] = [
                    'question_id' => $question->id,
                    'option_id' => $selectedOptionId,
                    'is_correct' => (bool) $isCorrect,
                ];
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
            $isPassed,
            $answersToSave
        ) {
            $attempt->update([
                'score' => $score,
                'total_score' => $totalScore,
                'percentage' => $percentage,
                'is_passed' => $isPassed,
                'submitted_at' => now(),
            ]);

            $attempt->answers()->delete();
            foreach ($answersToSave as $answerData) {
                $attempt->answers()->create($answerData);
            }
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

    /**
     * Get student attempts history for a specific exam.
     */
    #[OA\Get(
        path: '/api/v1/student/exams/{exam}/attempts',
        operationId: 'studentExamAttempts',
        summary: 'Get attempts history for an exam',
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
            new OA\Response(response: 200, description: 'Attempts retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Exam not found'),
        ]
    )]
    public function attempts(Request $request, int $exam): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $examModel = Exam::query()
            ->where('is_active', true)
            ->with([
                'grade:id,name',
                'lesson.section',
            ])
            ->find($exam);

        if (! $examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (! $this->canStudentAccessExam($student, $examModel)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view this exam attempts.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.',
            ], 403);
        }

        $attempts = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $examModel->id)
            ->where('subscription_id', $subscription->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Exam attempts retrieved successfully.',
            'data' => [
                'exam' => [
                    'id' => $examModel->id,
                    'title' => $examModel->title,
                    'passing_percentage' => $examModel->passing_percentage,
                ],
                'attempts' => $attempts,
            ],
        ]);
    }

    private function canStudentAccessExam(Student $student, Exam $exam): bool
    {
        if (! $student->grade_id) {
            return false;
        }

        if ($exam->lesson_id !== null) {
            return (bool) ($exam->lesson?->section && $exam->lesson->section->grade_id === $student->grade_id);
        }

        return $exam->grade_id === null || $exam->grade_id === $student->grade_id;
    }

    private function getExamLockMessage(Student $student, int $subscriptionId, Exam $exam): ?string
    {
        if (! $exam->lesson) {
            return null;
        }

        if (! $this->learningProgressService->isLessonUnlocked(
            $student->id,
            $subscriptionId,
            $exam->lesson
        )) {
            return 'This exam is locked. Complete previous lessons and exams first.';
        }

        if (! $this->learningProgressService->isLessonCompleted(
            $student->id,
            $subscriptionId,
            $exam->lesson
        )) {
            return 'This exam is locked. Complete all lesson videos first.';
        }

        return null;
    }
}
