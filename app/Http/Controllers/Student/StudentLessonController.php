<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\LearningProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Student Lessons',
    description: 'Student lesson endpoints'
)]
class StudentLessonController extends Controller
{
    public function __construct(
        private LearningProgressService $learningProgressService
    ) {
    }

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

        if (!$student->grade_id) {
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

        if (!$subscription) {
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
                        ]);
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
}