<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Exams',
    description: 'Admin exam, question and option management endpoints'
)]
class AdminExamController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/exams',
        operationId: 'adminExamsIndex',
        summary: 'Get all exams',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Exams retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(): JsonResponse
    {
        $exams = Exam::query()
            ->with([
                'lesson:id,section_id,title',
                'lesson.section:id,grade_id,name',
            ])
            ->withCount('questions')
            ->orderBy('lesson_id')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Exams retrieved successfully.',
            'data' => [
                'exams' => $exams,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/exams',
        operationId: 'adminExamsStore',
        summary: 'Create exam',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['lesson_id', 'title', 'passing_percentage'],
                properties: [
                    new OA\Property(
                        property: 'lesson_id',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        example: 'اختبار الدرس الأول'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'اختبار على محتوى الدرس'
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
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Exam created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lesson_id' => ['required', 'integer', 'exists:lessons,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'passing_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $lesson = Lesson::query()
            ->with('exam')
            ->find($validated['lesson_id']);

        if (!$lesson) {
            return response()->json([
                'success' => false,
                'message' => 'Lesson not found.',
            ], 404);
        }

        if ($lesson->exam) {
            return response()->json([
                'success' => false,
                'message' => 'This lesson already has an exam.',
            ], 422);
        }

        $exam = Exam::create([
            'lesson_id' => $validated['lesson_id'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? null,
            'passing_percentage' => $validated['passing_percentage'],
            'is_active' => true,
        ]);

        $exam->load([
            'lesson:id,section_id,title',
            'lesson.section:id,grade_id,name',
            'questions',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully.',
            'data' => [
                'exam' => $exam,
            ],
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/exams/{exam}',
        operationId: 'adminExamsShow',
        summary: 'Get exam details',
        tags: ['Admin Exams'],
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
            new OA\Response(response: 404, description: 'Exam not found'),
        ]
    )]
    public function show(int $exam): JsonResponse
    {
        $examModel = Exam::query()
            ->with([
                'lesson:id,section_id,title',
                'lesson.section:id,grade_id,name',
                'questions.options',
            ])
            ->find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam retrieved successfully.',
            'data' => [
                'exam' => $examModel,
            ],
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/exams/{exam}',
        operationId: 'adminExamsUpdate',
        summary: 'Update exam',
        tags: ['Admin Exams'],
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
                properties: [
                    new OA\Property(
                        property: 'title',
                        type: 'string',
                        example: 'اختبار الدرس الأول'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'اختبار على محتوى الدرس'
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
                        property: 'is_active',
                        type: 'boolean',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Exam updated successfully'),
            new OA\Response(response: 404, description: 'Exam not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(Request $request, int $exam): JsonResponse
    {
        $examModel = Exam::find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'duration_minutes' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'passing_percentage' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $examModel->update($validated);

        $examModel->load([
            'lesson:id,section_id,title',
            'lesson.section:id,grade_id,name',
            'questions.options',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully.',
            'data' => [
                'exam' => $examModel,
            ],
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/exams/{exam}',
        operationId: 'adminExamsDestroy',
        summary: 'Delete exam',
        tags: ['Admin Exams'],
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
            new OA\Response(response: 200, description: 'Exam deleted successfully'),
            new OA\Response(response: 404, description: 'Exam not found'),
        ]
    )]
    public function destroy(int $exam): JsonResponse
    {
        $examModel = Exam::find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $examModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam deleted successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/exams/{exam}/questions',
        operationId: 'adminExamQuestionStore',
        summary: 'Add question to exam',
        description: 'Create a new question inside an existing exam. The sort order is generated automatically.',
        tags: ['Admin Exams'],
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
            description: 'Question data',
            content: new OA\JsonContent(
                required: ['question_text', 'points'],
                properties: [
                    new OA\Property(
                        property: 'question_text',
                        type: 'string',
                        description: 'The question text',
                        example: 'ما هو ناتج 2 + 2؟'
                    ),
                    new OA\Property(
                        property: 'points',
                        type: 'integer',
                        description: 'Number of points for this question',
                        minimum: 1,
                        example: 1
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Question created successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Exam not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function storeQuestion(Request $request, int $exam): JsonResponse
    {
        $examModel = Exam::find($exam);

        if (!$examModel) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found.',
            ], 404);
        }

        $validated = $request->validate([
            'question_text' => ['required', 'string'],
            'points' => ['required', 'integer', 'min:1'],
        ]);

        $sortOrder = ((int) $examModel->questions()->max('sort_order')) + 1;

        $question = $examModel->questions()->create([
            'question_text' => $validated['question_text'],
            'points' => $validated['points'],
            'sort_order' => $sortOrder,
        ]);

        $question->load('options');

        return response()->json([
            'success' => true,
            'message' => 'Question created successfully.',
            'data' => [
                'question' => $question,
            ],
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/questions/{question}',
        operationId: 'adminQuestionUpdate',
        summary: 'Update question',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'question',
                description: 'Question ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
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
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Question updated successfully'
            ),
        ]
    )]
    public function updateQuestion(Request $request, int $question): JsonResponse
    {
        $questionModel = Question::find($question);

        if (!$questionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
            ], 404);
        }

        $validated = $request->validate([
            'question_text' => ['sometimes', 'required', 'string'],
            'points' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $questionModel->update($validated);

        $questionModel->load('options');

        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully.',
            'data' => [
                'question' => $questionModel,
            ],
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/questions/{question}',
        operationId: 'adminQuestionDestroy',
        summary: 'Delete question',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'question',
                description: 'Question ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Question deleted successfully'
            ),
        ]
    )]
    public function destroyQuestion(int $question): JsonResponse
    {
        $questionModel = Question::find($question);

        if (!$questionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
            ], 404);
        }

        $questionModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Question deleted successfully.',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/questions/{question}/options',
        operationId: 'adminQuestionOptionStore',
        summary: 'Add option to question',
        description: 'Create an option. The sort order is generated automatically and each question can have only one correct option.',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'question',
                description: 'Question ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['option_text', 'is_correct'],
                properties: [
                    new OA\Property(
                        property: 'option_text',
                        type: 'string',
                        description: 'Option text',
                        example: '4'
                    ),
                    new OA\Property(
                        property: 'is_correct',
                        type: 'boolean',
                        description: 'Whether this option is the correct answer',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Option created successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Question not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function storeOption(Request $request, int $question): JsonResponse
    {
        $questionModel = Question::find($question);

        if (!$questionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Question not found.',
            ], 404);
        }

        $validated = $request->validate([
            'option_text' => ['required', 'string'],
            'is_correct' => ['required', 'boolean'],
        ]);

        if ($validated['is_correct']) {
            $hasCorrectOption = $questionModel
                ->options()
                ->where('is_correct', true)
                ->exists();

            if ($hasCorrectOption) {
                return response()->json([
                    'success' => false,
                    'message' => 'This question already has a correct answer.',
                ], 422);
            }
        }

        $sortOrder = ((int) $questionModel->options()->max('sort_order')) + 1;

        $option = $questionModel->options()->create([
            'option_text' => $validated['option_text'],
            'is_correct' => $validated['is_correct'],
            'sort_order' => $sortOrder,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Option created successfully.',
            'data' => [
                'option' => $option,
            ],
        ], 201);
    }

    #[OA\Put(
        path: '/api/v1/admin/options/{option}',
        operationId: 'adminOptionUpdate',
        summary: 'Update option',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'option',
                description: 'Option ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
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
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Option updated successfully'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function updateOption(Request $request, int $option): JsonResponse
    {
        $optionModel = Option::find($option);

        if (!$optionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Option not found.',
            ], 404);
        }

        $validated = $request->validate([
            'option_text' => ['sometimes', 'required', 'string'],
            'is_correct' => ['sometimes', 'required', 'boolean'],
        ]);

        if (
            array_key_exists('is_correct', $validated) &&
            $validated['is_correct']
        ) {
            $hasAnotherCorrectOption = $optionModel
                ->question
                ->options()
                ->where('is_correct', true)
                ->where('id', '!=', $optionModel->id)
                ->exists();

            if ($hasAnotherCorrectOption) {
                return response()->json([
                    'success' => false,
                    'message' => 'This question already has another correct answer.',
                ], 422);
            }
        }

        $optionModel->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Option updated successfully.',
            'data' => [
                'option' => $optionModel->fresh(),
            ],
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/options/{option}',
        operationId: 'adminOptionDestroy',
        summary: 'Delete option',
        tags: ['Admin Exams'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'option',
                description: 'Option ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Option deleted successfully'
            ),
        ]
    )]
    public function destroyOption(int $option): JsonResponse
    {
        $optionModel = Option::find($option);

        if (!$optionModel) {
            return response()->json([
                'success' => false,
                'message' => 'Option not found.',
            ], 404);
        }

        $optionModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Option deleted successfully.',
        ]);
    }
}