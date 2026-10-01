<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Subscription;
use App\Services\Admin\AdminActivationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminActivationController extends Controller
{
    public function __construct(
        private AdminActivationService $adminActivationService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/activation-requests',
        operationId: 'getPendingActivationRequests',
        summary: 'Get pending student activation requests',
        description: 'Returns student activation requests that are waiting for admin approval.',
        tags: ['Admin Activations'],
        security: [
            ['sanctum' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pending activation requests retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            )
        ]
    )]
    public function index(
        Request $request
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $requests = $this->adminActivationService
            ->getPendingRequests();

        return response()->json([
            'success' => true,
            'message' => 'Pending activation requests retrieved successfully.',
            'data' => $requests,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/activation-requests/{subscription}/approve',
        operationId: 'approveStudentActivation',
        summary: 'Approve a student activation request',
        description: 'Approves a pending student activation request and activates its subscription and subscription code.',
        tags: ['Admin Activations'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'subscription',
                in: 'path',
                required: true,
                description: 'Subscription ID',
                schema: new OA\Schema(type: 'integer'),
                example: 3
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student activation approved successfully'
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
                description: 'Subscription not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Activation request has already been processed'
            )
        ]
    )]
    public function approve(
        Request $request,
        Subscription $subscription
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $subscription = $this->adminActivationService
            ->approve($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Student activation approved successfully.',
            'data' => [
                'student' => [
                    'id' => $subscription->student->id,
                    'name' => trim(
                        $subscription->student->first_name . ' ' .
                        $subscription->student->last_name
                    ),
                ],
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'approval_status' =>
                        $subscription->approval_status,
                    'start_date' =>
                        $subscription->start_date?->toDateString(),
                    'approved_at' =>
                        $subscription->approved_at?->toISOString(),
                ],
                'subscription_code' => [
                    'code' =>
                        $subscription->subscriptionCode->code,
                    'approval_status' =>
                        $subscription->subscriptionCode->approval_status,
                ],
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/activation-requests/{subscription}/cancel',
        operationId: 'cancelStudentActivation',
        summary: 'Cancel a student activation request',
        description: 'Rejects a pending student activation request. The subscription becomes cancelled and the subscription code becomes rejected.',
        tags: ['Admin Activations'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'subscription',
                in: 'path',
                required: true,
                description: 'Subscription ID',
                schema: new OA\Schema(type: 'integer'),
                example: 3
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student activation request cancelled successfully'
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
                description: 'Subscription not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Activation request has already been processed'
            )
        ]
    )]
    public function cancel(
        Request $request,
        Subscription $subscription
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $subscription = $this->adminActivationService
            ->cancel($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Student activation request cancelled successfully.',
            'data' => [
                'student' => [
                    'id' => $subscription->student->id,
                    'name' => trim(
                        $subscription->student->first_name . ' ' .
                        $subscription->student->last_name
                    ),
                ],
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'approval_status' =>
                        $subscription->approval_status,
                ],
                'subscription_code' => [
                    'id' =>
                        $subscription->subscriptionCode->id,
                    'approval_status' =>
                        $subscription->subscriptionCode->approval_status,
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/approved-students',
        operationId: 'getApprovedStudents',
        summary: 'Get approved students',
        description: 'Returns students who have at least one approved subscription.',
        tags: ['Admin Activations'],
        security: [
            ['sanctum' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Approved students retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Admin access required'
            )
        ]
    )]
    public function approvedStudents(
        Request $request
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $students = $this->adminActivationService
            ->getApprovedStudents();

        return response()->json([
            'success' => true,
            'message' => 'Approved students retrieved successfully.',
            'data' => $students,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/subscriptions/{subscription}/deactivate',
        operationId: 'deactivateSubscription',
        summary: 'Deactivate an active subscription',
        description: 'Deactivates an active student subscription.',
        tags: ['Admin Activations'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'subscription',
                in: 'path',
                required: true,
                description: 'Subscription ID',
                schema: new OA\Schema(type: 'integer'),
                example: 3
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription deactivated successfully'
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
                description: 'Subscription not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Subscription cannot be deactivated'
            )
        ]
    )]
    public function deactivateSubscription(
        Request $request,
        Subscription $subscription
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $subscription = $this->adminActivationService
            ->deactivateSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription deactivated successfully.',
            'data' => [
                'student' => [
                    'id' => $subscription->student->id,
                    'name' => trim(
                        $subscription->student->first_name . ' ' .
                        $subscription->student->last_name
                    ),
                ],
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'approval_status' =>
                        $subscription->approval_status,
                ],
            ],
        ]);
    }
}