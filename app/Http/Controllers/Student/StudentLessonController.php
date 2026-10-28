<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Question;
use App\Models\Student;
use App\Services\LearningProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'Student Lessons',
    description: 'Student lesson endpoints'
)]
class StudentLessonController extends Controller
{
    public function __construct(
        private LearningProgressService $learningProgressService
    ) {}

    #[OA\Get(
        path: '/api/v1/student/lessons',
        operationId: 'studentLessonsIndex',
        summary: 'Get student lessons with learning progress',
        tags: ['Student Lessons'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lessons retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => true,
                'message' => 'No grade is assigned to this student.',
                'data' => [
                    'lessons' => [],
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
                    'lessons' => [],
                ],
            ]);
        }

        $lessons = Lesson::query()
            ->where('is_active', true)
            ->whereHas('section', function ($query) use ($student) {
                $query
                    ->where('grade_id', $student->grade_id)
                    ->where('is_active', true);
            })
            ->with([
                'section:id,grade_id,name,sort_order',

                'videos' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->select([
                            'id',
                            'lesson_id',
                            'title',
                            'video_url',
                            'sort_order',
                            'duration_seconds',
                            'completion_percentage',
                            'is_active',
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },

                'exam' => function ($query) {
                    $query
                        ->where('is_active', true)
                        ->select([
                            'id',
                            'lesson_id',
                            'title',
                            'description',
                            'duration_minutes',
                            'passing_percentage',
                            'is_active',
                        ])
                        ->withCount('questions');
                },
            ])
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $lessons = $lessons->map(function (Lesson $lesson) use (
            $student,
            $subscription
        ) {
            $lessonStatus = $this->learningProgressService
                ->getLessonStatus(
                    $student->id,
                    $subscription->id,
                    $lesson
                );

            $lesson->setAttribute(
                'learning_status',
                $lessonStatus
            );

            $lesson->videos->each(function ($video) use (
                $student,
                $subscription
            ) {
                $videoStatus = $this->learningProgressService
                    ->getVideoStatus(
                        $student->id,
                        $subscription->id,
                        $video
                    );

                $video->setAttribute(
                    'learning_status',
                    $videoStatus
                );
            });

            return $lesson;
        });

        return response()->json([
            'success' => true,
            'message' => 'Lessons retrieved successfully.',
            'data' => [
                'subscription_id' => $subscription->id,
                'lessons' => $lessons,
            ],
        ]);
    }

    /**
     * Show a single lesson with videos, materials, exam, and learning status.
     */
    #[OA\Get(
        path: '/api/v1/student/lessons/{lesson}',
        operationId: 'studentLessonsShow',
        summary: 'Get details of a single lesson for student',
        tags: ['Student Lessons'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lesson',
                description: 'Lesson ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lesson retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Access denied'),
            new OA\Response(response: 404, description: 'Lesson not found'),
        ]
    )]
    public function show(Request $request, int $lesson): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();
        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'No grade is assigned to this student.',
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

        $lessonModel = Lesson::query()
            ->where('is_active', true)
            ->whereHas('section', function ($query) use ($student) {
                $query->where('grade_id', $student->grade_id)
                    ->where('is_active', true);
            })
            ->with([
                'section:id,grade_id,name,sort_order',
                'videos' => function ($query) {
                    $query->where('is_active', true)
                        ->select([
                            'id',
                            'lesson_id',
                            'title',
                            'video_url',
                            'sort_order',
                            'duration_seconds',
                            'completion_percentage',
                            'is_active',
                        ])
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'exam' => function ($query) {
                    $query->where('is_active', true)
                        ->select([
                            'id',
                            'lesson_id',
                            'title',
                            'passing_percentage',
                            'duration_minutes',
                            'is_active',
                        ])
                        ->withCount('questions');
                },
                'materials' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->find($lesson);

        if (! $lessonModel) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found or not in your grade.',
            ], 404);
        }

        $lessonStatus = $this->learningProgressService
            ->getLessonStatus(
                $student->id,
                $subscription->id,
                $lessonModel
            );

        $lessonModel->setAttribute('learning_status', $lessonStatus);

        $lessonModel->videos->each(function ($video) use ($student, $subscription) {
            $videoStatus = $this->learningProgressService
                ->getVideoStatus(
                    $student->id,
                    $subscription->id,
                    $video
                );

            $video->setAttribute('learning_status', $videoStatus);
        });

        return response()->json([
            'success' => true,
            'message' => 'Lesson retrieved successfully.',
            'data' => [
                'subscription_id' => $subscription->id,
                'lesson' => $lessonModel,
            ],
        ]);
    }

    /**
     * Download a lesson material PDF or document.
     */
    #[OA\Get(
        path: '/api/v1/student/materials/{material}/download',
        operationId: 'studentMaterialsDownload',
        summary: 'Download lesson material file',
        tags: ['Student Lessons'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'material',
                description: 'Material ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'File download initiated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Access denied or lesson locked'),
            new OA\Response(response: 404, description: 'Material not found'),
        ]
    )]
    public function downloadMaterial(Request $request, int $material): Response
    {
        /** @var Student $student */
        $student = $request->user();
        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'No grade is assigned to this student.',
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

        $materialModel = Material::query()
            ->where('id', $material)
            ->where('is_active', true)
            ->with(['lesson.section'])
            ->first();

        if (! $materialModel) {
            return response()->json([
                'success' => false,
                'message' => 'Material not found.',
            ], 404);
        }

        if (! $materialModel->is_downloadable) {
            return response()->json([
                'success' => false,
                'message' => 'This material is not downloadable.',
            ], 403);
        }

        if (
            ! $materialModel->lesson?->section ||
            $materialModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This material belongs to another grade.',
            ], 403);
        }

        if (! $this->learningProgressService->isLessonUnlocked($student->id, $subscription->id, $materialModel->lesson)) {
            return response()->json([
                'success' => false,
                'message' => 'This lesson is locked. Complete previous lessons and exams first.',
            ], 403);
        }

        if (empty($materialModel->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File path is missing for this material.',
            ], 404);
        }

        $safeTitle = preg_replace('/[^\p{L}\p{N}\-_.]/u', '_', $materialModel->title);
        $ext = pathinfo($materialModel->file_path, PATHINFO_EXTENSION) ?: 'pdf';
        $fileName = ($safeTitle ?: 'material_'.$materialModel->id).'.'.$ext;

        if (Storage::disk('public')->exists($materialModel->file_path)) {
            return Storage::disk('public')->download($materialModel->file_path, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        if (str_contains($materialModel->file_path, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $materialModel->file_path);
            if (Storage::disk('public')->exists($storagePath)) {
                return Storage::disk('public')->download($storagePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            }
        }

        if (filter_var($materialModel->file_path, FILTER_VALIDATE_URL)) {
            return redirect()->away($materialModel->file_path);
        }

        return response()->json([
            'success' => false,
            'message' => 'The file could not be located on the server.',
        ], 404);
    }

    #[OA\Get(
        path: '/api/v1/student/lessons/{lesson}/quiz/answers',
        operationId: 'studentLessonQuizAnswers',
        summary: 'Get student answers for a lesson quiz',
        description: 'Returns the student answers, selected options, correct options, and results for a specific lesson quiz attempt.',
        tags: ['Student Lessons'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lesson',
                description: 'Lesson ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
            new OA\Parameter(
                name: 'attempt_id',
                description: 'Optional specific attempt ID. If omitted, returns the latest submitted attempt.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Quiz answers retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden / Subscription required'),
            new OA\Response(response: 404, description: 'Lesson, quiz, or attempt not found'),
        ]
    )]
    public function quizAnswers(Request $request, int $lesson): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'No grade is assigned to this student.',
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

        $lessonModel = Lesson::query()
            ->with(['section', 'exam'])
            ->find($lesson);

        if (! $lessonModel) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
            ], 404);
        }

        if ($lessonModel->section && $lessonModel->section->grade_id !== $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this lesson.',
            ], 403);
        }

        if (! $lessonModel->exam) {
            return response()->json([
                'success' => false,
                'message' => 'This lesson does not have a quiz.',
            ], 404);
        }

        $attemptQuery = ExamAttempt::query()
            ->where('student_id', $student->id)
            ->where('exam_id', $lessonModel->exam->id)
            ->whereNotNull('submitted_at');

        if ($request->filled('attempt_id')) {
            $attemptQuery->where('id', $request->integer('attempt_id'));
        } else {
            $attemptQuery->latest('id');
        }

        $attempt = $attemptQuery->with(['answers.option', 'answers.question'])->first();

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'No submitted quiz attempts found for this lesson.',
            ], 404);
        }

        $questions = Question::where('exam_id', $lessonModel->exam->id)
            ->with('options')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $answersByQuestion = $attempt->answers->keyBy('question_id');

        $detailedAnswers = $questions->map(function ($question) use ($answersByQuestion) {
            $studentAnswer = $answersByQuestion->get($question->id);
            $selectedOption = $studentAnswer?->option;
            $correctOption = $question->options->firstWhere('is_correct', true);

            return [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'points' => (int) $question->points,
                'sort_order' => (int) $question->sort_order,
                'is_answered' => $studentAnswer !== null,
                'is_correct' => $studentAnswer ? (bool) $studentAnswer->is_correct : false,
                'selected_option' => $selectedOption ? [
                    'id' => $selectedOption->id,
                    'option_text' => $selectedOption->option_text,
                    'is_correct' => (bool) $selectedOption->is_correct,
                ] : null,
                'correct_option' => $correctOption ? [
                    'id' => $correctOption->id,
                    'option_text' => $correctOption->option_text,
                ] : null,
                'options' => $question->options->map(function ($opt) use ($selectedOption) {
                    return [
                        'id' => $opt->id,
                        'option_text' => $opt->option_text,
                        'is_correct' => (bool) $opt->is_correct,
                        'is_selected' => $selectedOption && $selectedOption->id === $opt->id,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Quiz answers retrieved successfully.',
            'data' => [
                'lesson' => [
                    'id' => $lessonModel->id,
                    'title' => $lessonModel->title,
                ],
                'exam' => [
                    'id' => $lessonModel->exam->id,
                    'title' => $lessonModel->exam->title,
                    'passing_percentage' => $lessonModel->exam->passing_percentage !== null
                        ? (int) $lessonModel->exam->passing_percentage
                        : null,
                ],
                'attempt' => [
                    'id' => $attempt->id,
                    'score' => (int) $attempt->score,
                    'total_score' => (int) $attempt->total_score,
                    'percentage' => (string) $attempt->percentage,
                    'is_passed' => (bool) $attempt->is_passed,
                    'started_at' => $attempt->started_at?->toIso8601String(),
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                ],
                'answers' => $detailedAnswers,
            ],
        ]);
    }
}
