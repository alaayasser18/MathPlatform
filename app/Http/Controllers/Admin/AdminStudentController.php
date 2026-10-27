<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Http\Resources\Admin\StudentResource;
use App\Models\Admin;
use App\Models\ExamAttempt;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Student;
use App\Services\Admin\AdminStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminStudentController extends Controller
{
    public function __construct(
        private AdminStudentService $adminStudentService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/students',
        operationId: 'getAllStudents',
        summary: 'Get all students',
        description: 'Returns a paginated list of all students.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                ),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Students retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
        ]
    )]
    public function index(Request $request)
    {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $students = $this->adminStudentService
            ->getAllStudents();

        return StudentResource::collection($students);
    }

    #[OA\Get(
        path: '/api/v1/admin/students/active',
        operationId: 'getActiveStudents',
        summary: 'Get active approved students',
        description: 'Returns only students who have an active approved subscription.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'grade_id',
                in: 'query',
                required: false,
                description: 'Filter by Grade ID',
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Active students retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
        ]
    )]
    public function activeStudents(Request $request): JsonResponse
    {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $query = Student::query()
            ->with([
                'grade:id,name',
                'subscriptions' => function ($q) {
                    $q->where('approval_status', 'approved')
                        ->where('status', 'active')
                        ->with('subscriptionCode');
                },
            ])
            ->whereHas('subscriptions', function ($q) {
                $q->where('approval_status', 'approved')
                    ->where('status', 'active');
            });

        if ($request->has('grade_id') && (int) $request->query('grade_id') > 0) {
            $query->where('grade_id', (int) $request->query('grade_id'));
        }

        $students = $query->latest('id')->get();

        return response()->json([
            'success' => true,
            'message' => 'Active students retrieved successfully.',
            'data' => StudentResource::collection($students),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/students/{student}',
        operationId: 'getStudentByAdmin',
        summary: 'Get student details',
        description: 'Returns the details of a specific student.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
            new OA\Response(
                response: 404,
                description: 'Student not found'
            ),
        ]
    )]
    public function show(
        Request $request,
        Student $student
    ) {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->getStudent($student);

        return new StudentResource($student);
    }

    #[OA\Post(
        path: '/api/v1/admin/students',
        operationId: 'createStudentByAdmin',
        summary: 'Create student manually by admin',
        description: 'Creates a student, enrollment, active subscription, and an approved subscription code. No approval is required when the student is created by an admin.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'first_name',
                    'last_name',
                    'phone',
                    'parent_phone',
                    'grade_id',
                ],
                properties: [
                    new OA\Property(
                        property: 'first_name',
                        type: 'string',
                        example: 'Ahmed'
                    ),
                    new OA\Property(
                        property: 'last_name',
                        type: 'string',
                        example: 'Ali'
                    ),
                    new OA\Property(
                        property: 'phone',
                        type: 'string',
                        example: '01012345678'
                    ),
                    new OA\Property(
                        property: 'parent_phone',
                        type: 'string',
                        example: '01112345678'
                    ),
                    new OA\Property(
                        property: 'grade_id',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'start_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2026-09-18'
                    ),
                    new OA\Property(
                        property: 'end_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2027-09-18'
                    ),
                    new OA\Property(
                        property: 'code_expires_at',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2027-09-18'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Student created successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function store(
        Request $request,
        CreateStudentRequest $createStudentRequest
    ): JsonResponse|StudentResource {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->createStudent(
                $createStudentRequest->validated()
            );

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/v1/admin/students/{student}',
        operationId: 'updateStudentByAdmin',
        summary: 'Update student',
        description: 'Updates the basic information of a student.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'first_name',
                    'last_name',
                    'phone',
                    'parent_phone',
                    'grade_id',
                ],
                properties: [
                    new OA\Property(
                        property: 'first_name',
                        type: 'string',
                        example: 'Ahmed'
                    ),
                    new OA\Property(
                        property: 'last_name',
                        type: 'string',
                        example: 'Ali'
                    ),
                    new OA\Property(
                        property: 'phone',
                        type: 'string',
                        example: '01012345678'
                    ),
                    new OA\Property(
                        property: 'parent_phone',
                        type: 'string',
                        example: '01112345678'
                    ),
                    new OA\Property(
                        property: 'grade_id',
                        type: 'integer',
                        example: 1
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student updated successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
            new OA\Response(
                response: 404,
                description: 'Student not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function update(
        Request $request,
        UpdateStudentRequest $updateStudentRequest,
        Student $student
    ): JsonResponse|StudentResource {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->updateStudent(
                $student,
                $updateStudentRequest->validated()
            );

        return new StudentResource($student);
    }

    #[OA\Delete(
        path: '/api/v1/admin/students/{student}',
        operationId: 'deleteStudentByAdmin',
        summary: 'Delete student',
        description: 'Permanently deletes a student and all related records from the database.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student deleted successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            ),
            new OA\Response(
                response: 404,
                description: 'Student not found'
            ),
        ]
    )]
    public function destroy(
        Request $request,
        Student $student
    ): JsonResponse {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $deletedStudent = $this->adminStudentService
            ->deleteStudent($student);

        return response()->json([
            'success' => true,
            'message' => 'Student deleted successfully.',
            'data' => [
                'student' => $deletedStudent,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/students/{student}/lessons/{lesson}/quiz/answers',
        operationId: 'adminStudentLessonQuizAnswers',
        summary: 'Get quiz answers of a student for a lesson',
        description: 'Returns the answers and attempt details of a specific student on a specific lesson quiz.',
        tags: ['Admin Students'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'student',
                description: 'Student ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
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
                description: 'Optional attempt ID. If omitted, returns the latest submitted attempt.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Quiz answers retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Admin access required'),
            new OA\Response(response: 404, description: 'Student, lesson, or quiz attempt not found'),
        ]
    )]
    public function quizAnswers(Request $request, int $student, int $lesson): JsonResponse
    {
        if (! $request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $studentModel = Student::find($student);
        if (! $studentModel) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 404);
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

        if (! $lessonModel->exam) {
            return response()->json([
                'success' => false,
                'message' => 'This lesson does not have a quiz.',
            ], 404);
        }

        $attemptQuery = ExamAttempt::query()
            ->where('student_id', $studentModel->id)
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
                'message' => 'No submitted quiz attempts found for this student on this lesson.',
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
            'message' => 'Student quiz answers retrieved successfully.',
            'data' => [
                'student' => [
                    'id' => $studentModel->id,
                    'first_name' => $studentModel->first_name,
                    'last_name' => $studentModel->last_name,
                    'phone' => $studentModel->phone,
                ],
                'lesson' => [
                    'id' => $lessonModel->id,
                    'title' => $lessonModel->title,
                ],
                'exam' => [
                    'id' => $lessonModel->exam->id,
                    'title' => $lessonModel->exam->title,
                    'passing_percentage' => (int) $lessonModel->exam->passing_percentage,
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
