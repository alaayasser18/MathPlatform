<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Services\LearningProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use RuntimeException;

#[OA\Tag(
    name: 'Student Videos',
    description: 'Student video progress and access endpoints'
)]
class StudentVideoController extends Controller
{
    public function __construct(
        private LearningProgressService $learningProgressService
    ) {
    }

    /**
     * Get a single video with its access status.
     */
    #[OA\Get(
        path: '/api/v1/student/videos/{video}',
        operationId: 'studentVideoShow',
        summary: 'Get student video',
        tags: ['Student Videos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'video',
                description: 'Video ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Video retrieved successfully'
            ),
            new OA\Response(
                response: 403,
                description: 'Video is locked'
            ),
            new OA\Response(
                response: 404,
                description: 'Video not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function show(Request $request, int $video): JsonResponse
    {
        $student = $request->user();

        $videoModel = Video::query()
            ->where('is_active', true)
            ->with([
                'lesson.section',
                'lesson.exam',
            ])
            ->find($video);

        if (!$videoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure the video belongs to the student's grade.
        |--------------------------------------------------------------------------
        */

        $student->loadMissing('grade');

        if (
            !$student->grade_id ||
            !$videoModel->lesson?->section ||
            $videoModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this video.',
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
            ->isVideoUnlocked(
                $student->id,
                $subscription->id,
                $videoModel
            );

        if (!$isUnlocked) {
            return response()->json([
                'success' => false,
                'message' => 'This video is locked. Complete the previous video first.',
            ], 403);
        }

        $status = $this->learningProgressService
            ->getVideoStatus(
                $student->id,
                $subscription->id,
                $videoModel
            );

        return response()->json([
            'success' => true,
            'message' => 'Video retrieved successfully.',
            'data' => [
                'video' => $videoModel,
                'progress' => $status,
            ],
        ]);
    }

    /**
     * Update video watching progress.
     */
    #[OA\Post(
        path: '/api/v1/student/videos/{video}/progress',
        operationId: 'studentVideoProgress',
        summary: 'Update video progress',
        tags: ['Student Videos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'video',
                description: 'Video ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'current_position_seconds',
                    'watched_duration_seconds',
                    'completion_percentage',
                ],
                properties: [
                    new OA\Property(
                        property: 'current_position_seconds',
                        type: 'integer',
                        minimum: 0,
                        example: 120
                    ),
                    new OA\Property(
                        property: 'watched_duration_seconds',
                        type: 'integer',
                        minimum: 0,
                        example: 120
                    ),
                    new OA\Property(
                        property: 'completion_percentage',
                        type: 'integer',
                        minimum: 0,
                        maximum: 100,
                        example: 90
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Video progress updated successfully'
            ),
            new OA\Response(
                response: 403,
                description: 'Video is locked'
            ),
            new OA\Response(
                response: 404,
                description: 'Video not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
        ]
    )]
    public function updateProgress(
        Request $request,
        int $video
    ): JsonResponse {
        $validated = $request->validate([
            'current_position_seconds' => [
                'required',
                'integer',
                'min:0',
            ],

            'watched_duration_seconds' => [
                'required',
                'integer',
                'min:0',
            ],

            'completion_percentage' => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
        ]);

        $student = $request->user();

        $videoModel = Video::query()
            ->where('is_active', true)
            ->with('lesson.section')
            ->find($video);

        if (!$videoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
        }

        $student->loadMissing('grade');

        if (
            !$student->grade_id ||
            !$videoModel->lesson?->section ||
            $videoModel->lesson->section->grade_id !== $student->grade_id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to access this video.',
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

        try {
            $progress = $this->learningProgressService
                ->updateVideoProgress(
                    $student->id,
                    $subscription->id,
                    $videoModel,
                    $validated['current_position_seconds'],
                    $validated['watched_duration_seconds'],
                    $validated['completion_percentage']
                );
        } catch (RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => $progress->is_completed
                ? 'Video completed successfully.'
                : 'Video progress updated successfully.',
            'data' => [
                'progress' => $progress,
                'is_completed' => (bool) $progress->is_completed,
                'completion_percentage' => (int) $progress->completion_percentage,
            ],
        ]);
    }
}