<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\SubscriptionCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminSubscriptionCodeController extends Controller
{
    public function __construct(
        private SubscriptionCodeService $subscriptionCodeService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/subscription-codes',
        operationId: 'getSubscriptionCodesByGrade',
        summary: 'Get student subscription codes by grade',
        description: 'Returns student names and their approved active subscription codes for a specific grade.',
        tags: ['Admin Subscription Codes'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'grade_id',
                in: 'query',
                required: true,
                description: 'Grade ID',
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription codes retrieved successfully'
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
                description: 'Grade not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $gradeId = (int) $request->query('grade_id');

        if ($gradeId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'The grade_id query parameter is required.',
            ], 422);
        }

        $result = $this->subscriptionCodeService
            ->getCodesByGrade($gradeId);

        $students = $result['students']
            ->map(function ($student) {
                $subscription = $student->subscriptions->first();

                return [
                    'student_id' => $student->id,

                    'student_name' => trim(
                        $student->first_name . ' ' .
                        $student->last_name
                    ),

                    'subscription_code' =>
                        $subscription?->subscriptionCode?->code,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'message' =>
                'Subscription codes retrieved successfully.',

            'data' => [
                'grade' => [
                    'id' => $result['grade']->id,
                    'name' => $result['grade']->name,
                ],

                'students' => $students,
            ],
        ]);
    }
}