<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\StoreQuizQuestionsRequest;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Lessons',
    description: 'Admin lesson management endpoints'
)]
class AdminLessonController extends Controller
{
    /**
     * Get all lessons.
     */
    #[OA\Get(
        path: '/api/v1/admin/lessons',
        operationId: 'adminLessonsIndex',
        summary: 'Get all lessons',
        tags: ['Admin Lessons'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'grade_id',
                description: 'Filter lessons by grade ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
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
        $query = Lesson::query()
            ->with([
                'section:id,grade_id,name,sort_order',
                'section.grade:id,name',
                'videos:id,lesson_id,title,video_url,sort_order,is_active',
                'exam.questions.options',
            ])
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->filled('grade_id')) {
            $query->whereHas('section', function ($q) use ($request) {
                $q->where('grade_id', $request->integer('grade_id'));
            });
        }

        $lessons = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Lessons retrieved successfully.',
            'data' => [
                'lessons' => $lessons,
            ],
        ]);
    }

    /**
     * Get details of a single lesson for admin.
     */
    #[OA\Get(
        path: '/api/v1/admin/lessons/{lesson}',
        operationId: 'adminLessonsShow',
        summary: 'Get details of a single lesson for admin',
        tags: ['Admin Lessons'],
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
            new OA\Response(
                response: 200,
                description: 'Lesson retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Lesson not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function show(int $lesson): JsonResponse
    {
        $lessonModel = Lesson::query()
            ->with([
                'section:id,grade_id,name,sort_order',
                'section.grade:id,name',
                'videos:id,lesson_id,title,video_url,sort_order,is_active',
                'exam.questions.options',
            ])
            ->find($lesson);

        if (! $lessonModel) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson retrieved successfully.',
            'data' => [
                'lesson' => $lessonModel,
            ],
        ]);
    }

    /**
     * Create a new lesson.
     */
    #[OA\Post(
        path: '/api/v1/admin/lessons',
        operationId: 'adminLessonsStore',
        summary: 'Create a new lesson',
        tags: ['Admin Lessons'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'grade_id',
                    'section_name',
                    'title',
                    'video_url',
                ],
                properties: [
                    new OA\Property(
                        property: 'grade_id',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'section_name',
                        type: 'string',
                        example: 'الجبر'
                    ),
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        example: 'المصفوفات'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'شرح درس المصفوفات'
                    ),
                    new OA\Property(
                        property: 'video_url',
                        type: 'string',
                        format: 'uri',
                        example: 'https://www.youtube.com/watch?v=XXXXXXXXXXX'
                    ),
                    new OA\Property(
                        property: 'quiz_title',
                        type: 'string',
                        nullable: true,
                        example: 'اختبار الدرس الأول'
                    ),
                    new OA\Property(
                        property: 'duration_minutes',
                        type: 'integer',
                        nullable: true,
                        example: 20
                    ),
                    new OA\Property(
                        property: 'passing_percentage',
                        type: 'integer',
                        nullable: true,
                        example: 60
                    ),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'question_text', type: 'string', example: 'ما هو ناتج 2 + 2؟'),
                                new OA\Property(property: 'points', type: 'integer', example: 1),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'option_text', type: 'string', example: '4'),
                                            new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Lesson created successfully'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function store(StoreLessonRequest $request): JsonResponse
    {
        $lesson = DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Find or create section
            |--------------------------------------------------------------------------
            */

            $section = Section::firstOrCreate(
                [
                    'grade_id' => $request->integer('grade_id'),
                    'name' => $request->string('section_name')->toString(),
                ],
                [
                    'description' => null,
                    'sort_order' => (
                        (int) Section::query()
                            ->where(
                                'grade_id',
                                $request->integer('grade_id')
                            )
                            ->max('sort_order')
                    ) + 1,
                    'is_active' => true,
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Next lesson order inside this section
            |--------------------------------------------------------------------------
            */

            $nextLessonOrder =
                ((int) $section->lessons()->max('sort_order')) + 1;

            /*
            |--------------------------------------------------------------------------
            | Create lesson
            |--------------------------------------------------------------------------
            */

            $lesson = $section->lessons()->create([
                'title' => $request->string('title')->toString(),
                'description' => $request->input('description'),
                'sort_order' => $nextLessonOrder,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create first video
            |--------------------------------------------------------------------------
            */

            $lesson->videos()->create([
                'title' => $request->string('title')->toString(),
                'video_url' => $request->string('video_url')->toString(),
                'sort_order' => 1,
                'duration_seconds' => 0,
                'completion_percentage' => 90,
                'is_active' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create exam & questions if provided
            |--------------------------------------------------------------------------
            */

            if ($request->filled('questions')) {
                $exam = Exam::create([
                    'lesson_id' => $lesson->id,
                    'title' => $request->input('quiz_title') ?? ($lesson->title.' - اختبار'),
                    'description' => $request->input('quiz_description'),
                    'duration_minutes' => $request->input('duration_minutes'),
                    'passing_percentage' => $request->integer('passing_percentage', 60),
                    'is_active' => true,
                ]);

                foreach ($request->input('questions') as $qIndex => $qData) {
                    $question = $exam->questions()->create([
                        'question_text' => $qData['question_text'],
                        'points' => $qData['points'] ?? 1,
                        'sort_order' => $qData['sort_order'] ?? ($qIndex + 1),
                    ]);

                    if (! empty($qData['options']) && is_array($qData['options'])) {
                        foreach ($qData['options'] as $optIndex => $optData) {
                            $question->options()->create([
                                'option_text' => $optData['option_text'],
                                'is_correct' => filter_var($optData['is_correct'], FILTER_VALIDATE_BOOLEAN),
                                'sort_order' => $optData['sort_order'] ?? ($optIndex + 1),
                            ]);
                        }
                    }
                }
            }

            return $lesson;
        });

        $lesson->load([
            'section:id,grade_id,name,sort_order',
            'section.grade:id,name',
            'videos:id,lesson_id,title,video_url,sort_order,is_active',
            'exam.questions.options',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully.',
            'data' => [
                'lesson' => $lesson,
            ],
        ], 201);
    }

    /**
     * Delete a lesson.
     */
    #[OA\Delete(
        path: '/api/v1/admin/lessons/{lesson}',
        operationId: 'adminLessonsDestroy',
        summary: 'Delete a lesson',
        tags: ['Admin Lessons'],
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
            new OA\Response(
                response: 200,
                description: 'Lesson deleted successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Lesson not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function destroy(int $lesson): JsonResponse
    {
        $lessonModel = Lesson::find($lesson);

        if (! $lessonModel) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
            ], 404);
        }

        $lessonModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lesson deleted successfully.',
        ]);
    }

    /**
     * Save quiz questions for a lesson.
     */
    #[OA\Post(
        path: '/api/v1/admin/lessons/{lesson}/quiz/questions',
        operationId: 'adminLessonQuizQuestionsStore',
        summary: 'Save quiz questions for a lesson',
        description: 'Creates or updates the quiz for the specified lesson and saves questions with their multiple-choice options.',
        tags: ['Admin Lessons'],
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
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Quiz questions and options data',
            content: new OA\JsonContent(
                required: ['questions'],
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        description: 'Optional custom title for the quiz',
                        example: 'اختبار الدرس الأول'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'اختبار تجريبي'
                    ),
                    new OA\Property(
                        property: 'duration_minutes',
                        type: 'integer',
                        nullable: true,
                        example: 20
                    ),
                    new OA\Property(
                        property: 'passing_percentage',
                        type: 'integer',
                        minimum: 1,
                        maximum: 100,
                        example: 60
                    ),
                    new OA\Property(
                        property: 'questions',
                        type: 'array',
                        items: new OA\Items(
                            required: ['question_text', 'points', 'options'],
                            properties: [
                                new OA\Property(
                                    property: 'question_text',
                                    type: 'string',
                                    example: 'ما هو ناتج 2 + 2؟'
                                ),
                                new OA\Property(
                                    property: 'points',
                                    type: 'integer',
                                    minimum: 1,
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'sort_order',
                                    type: 'integer',
                                    nullable: true,
                                    example: 1
                                ),
                                new OA\Property(
                                    property: 'options',
                                    type: 'array',
                                    items: new OA\Items(
                                        required: ['option_text', 'is_correct'],
                                        properties: [
                                            new OA\Property(
                                                property: 'option_text',
                                                type: 'string',
                                                example: '4'
                                            ),
                                            new OA\Property(
                                                property: 'is_correct',
                                                type: 'boolean',
                                                example: true
                                            ),
                                            new OA\Property(
                                                property: 'sort_order',
                                                type: 'integer',
                                                nullable: true,
                                                example: 1
                                            ),
                                        ]
                                    )
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Quiz questions saved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Lesson not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function storeQuizQuestions(StoreQuizQuestionsRequest $request, int $lesson): JsonResponse
    {
        $lessonModel = Lesson::with('exam')->find($lesson);

        if (! $lessonModel) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
            ], 404);
        }

        $validated = $request->validated();

        $exam = DB::transaction(function () use ($lessonModel, $validated) {
            $exam = $lessonModel->exam;

            if (! $exam) {
                $exam = Exam::create([
                    'lesson_id' => $lessonModel->id,
                    'title' => $validated['title'] ?? ($lessonModel->title.' - اختبار'),
                    'description' => $validated['description'] ?? null,
                    'duration_minutes' => $validated['duration_minutes'] ?? null,
                    'passing_percentage' => $validated['passing_percentage'] ?? 60,
                    'is_active' => true,
                ]);
            } else {
                $updates = [];
                if (isset($validated['title'])) {
                    $updates['title'] = $validated['title'];
                }
                if (array_key_exists('description', $validated)) {
                    $updates['description'] = $validated['description'];
                }
                if (array_key_exists('duration_minutes', $validated)) {
                    $updates['duration_minutes'] = $validated['duration_minutes'];
                }
                if (isset($validated['passing_percentage'])) {
                    $updates['passing_percentage'] = $validated['passing_percentage'];
                }

                if (! empty($updates)) {
                    $exam->update($updates);
                }
            }

            $currentMaxSort = (int) $exam->questions()->max('sort_order');

            foreach ($validated['questions'] as $qData) {
                $currentMaxSort++;
                $sortOrder = $qData['sort_order'] ?? $currentMaxSort;

                $question = $exam->questions()->create([
                    'question_text' => $qData['question_text'],
                    'points' => $qData['points'] ?? 1,
                    'sort_order' => $sortOrder,
                ]);

                if (! empty($qData['options']) && is_array($qData['options'])) {
                    $optSort = 0;
                    foreach ($qData['options'] as $optData) {
                        $optSort++;
                        $question->options()->create([
                            'option_text' => $optData['option_text'],
                            'is_correct' => filter_var($optData['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                            'sort_order' => $optData['sort_order'] ?? $optSort,
                        ]);
                    }
                }
            }

            return $exam;
        });

        $exam->load([
            'lesson:id,section_id,title',
            'lesson.section:id,grade_id,name',
            'questions.options',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Quiz questions saved successfully.',
            'data' => [
                'exam' => $exam,
                'questions_count' => $exam->questions->count(),
            ],
        ], 201);
    }
}
