<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Student\StudentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StudentProfileController extends Controller
{
    public function __construct(
        private StudentProfileService $studentProfileService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/student/profile',
        operationId: 'getStudentProfile',
        summary: 'Get student profile',
        description: 'Returns the authenticated student profile, active subscription, grade, and current trusted device.',
        tags: ['Student Profile'],
        security: [
            ['sanctum' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student profile retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'The current device is not trusted'
            ),
            new OA\Response(
                response: 422,
                description: 'No active subscription found'
            )
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $result = $this->studentProfileService
            ->getProfile($student);

        return response()->json([
            'success' => true,
            'message' => 'Student profile retrieved successfully.',
            'data' => [
                'student' => [
                    'id' => $result['student']->id,
                    'first_name' => $result['student']->first_name,
                    'last_name' => $result['student']->last_name,
                    'phone' => $result['student']->phone,
                    'parent_phone' => $result['student']->parent_phone,
                    'grade' => $result['student']->grade
                        ? [
                            'id' => $result['student']->grade->id,
                            'name' => $result['student']->grade->name,
                        ]
                        : null,
                ],

                'subscription' => [
                    'id' => $result['subscription']->id,
                    'status' => $result['subscription']->status,
                    'approval_status' => $result['subscription']->approval_status,
                    'start_date' => $result['subscription']->start_date?->toDateString(),
                    'end_date' => $result['subscription']->end_date?->toDateString(),
                ],

                'device' => $result['device']
                    ? [
                        'id' => $result['device']->id,
                        'device_name' => $result['device']->device_name,
                        'platform' => $result['device']->platform,
                        'browser' => $result['device']->browser,
                        'operating_system' => $result['device']->operating_system,
                        'is_trusted' => $result['device']->is_trusted,
                    ]
                    : null,
            ],
        ]);
    }
}