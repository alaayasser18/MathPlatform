<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Subscription;
use App\Services\Admin\AdminDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminDeviceController extends Controller
{
    public function __construct(
        private AdminDeviceService $adminDeviceService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/admin/subscriptions/{subscription}/reset-device',
        operationId: 'resetTrustedDevice',
        summary: 'Reset the trusted device for a subscription',
        description: 'Deactivates the current trusted device so the student can register a new device on the next login.',
        tags: ['Admin Devices'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'subscription',
                in: 'path',
                required: true,
                description: 'Subscription ID',
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 3
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trusted device reset successfully'
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
                description: 'No active trusted device'
            ),
            new OA\Response(
                response: 404,
                description: 'Subscription not found'
            )
        ]
    )]
    public function reset(
        Request $request,
        Subscription $subscription
    ): JsonResponse {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $device = $this->adminDeviceService
            ->resetTrustedDevice($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Trusted device reset successfully. The student can now log in from a new device.',
            'data' => [
                'subscription_id' => $subscription->id,
                'device' => [
                    'id' => $device->id,
                    'device_identifier' => $device->device_identifier,
                    'device_name' => $device->device_name,
                    'platform' => $device->platform,
                    'is_trusted' => $device->is_trusted,
                    'deactivated_at' => $device->deactivated_at?->toISOString(),
                ],
            ],
        ]);
    }
}