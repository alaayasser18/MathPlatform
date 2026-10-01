<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
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
                'exam:id,lesson_id,title,description,duration_minutes,passing_percentage,is_active',
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

            return $lesson;
        });

        $lesson->load([
            'section:id,grade_id,name,sort_order',
            'section.grade:id,name',
            'videos:id,lesson_id,title,video_url,sort_order,is_active',
            'exam:id,lesson_id,title,description,duration_minutes,passing_percentage,is_active',
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

        if (!$lessonModel) {
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
}