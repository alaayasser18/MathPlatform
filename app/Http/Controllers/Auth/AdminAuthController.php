<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminAuthController extends Controller
{
    public function __construct(
        private AdminAuthService $adminAuthService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/admin/auth/login',
        operationId: 'adminLogin',
        summary: 'Admin login',
        description: 'Authenticate an active admin using email and password and issue a Sanctum token.',
        tags: ['Admin Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'email',
                    'password'
                ],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'admin@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'Admin@12345'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin logged in successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Admin logged in successfully.'
                        ),
                        new OA\Property(
                            property: 'token',
                            type: 'string',
                            example: '1|abcdefghijklmnopqrstuvwxyz'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Invalid credentials or inactive account'
            )
        ]
    )]
    public function login(
        AdminLoginRequest $request
    ): JsonResponse {
        $result = $this->adminAuthService->login(
            $request->string('email')->toString(),
            $request->string('password')->toString()
        );

        return response()->json([
            'success' => true,
            'message' => 'Admin logged in successfully.',
            'token' => $result['token'],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/auth/logout',
        operationId: 'adminLogout',
        summary: 'Admin logout',
        description: 'Logout the authenticated admin by revoking the current Sanctum token.',
        security: [
            ['sanctum' => []]
        ],
        tags: ['Admin Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Admin logged out successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'success',
                            type: 'boolean',
                            example: true
                        ),
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Admin logged out successfully.'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'User is not authorized to access admin endpoints'
            )
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Admin logged out successfully.',
        ]);
    }
}