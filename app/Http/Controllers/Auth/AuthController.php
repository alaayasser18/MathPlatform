<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivateAccountRequest;
use App\Http\Requests\StudentLoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Activate Student Account
    |--------------------------------------------------------------------------
    */

    #[OA\Post(
        path: '/api/v1/auth/activate',
        operationId: 'activateStudentAccount',
        summary: 'Submit a student activation request',
        description: 'Creates a student, enrollment, subscription, and subscription code. The code remains hidden until admin approval.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'first_name',
                    'last_name',
                    'phone',
                    'parent_phone',
                    'grade_id'
                ],
                properties: [
                    new OA\Property(
                        property: 'first_name',
                        type: 'string',
                        example: 'Ahmed'
                    ),

                    new OA\Property(
                        property: 'last_name',
                        type: 'string',
                        example: 'Ali'
                    ),

                    new OA\Property(
                        property: 'phone',
                        type: 'string',
                        example: '01012345678'
                    ),

                    new OA\Property(
                        property: 'parent_phone',
                        type: 'string',
                        example: '01112345678'
                    ),

                    new OA\Property(
                        property: 'grade_id',
                        type: 'integer',
                        example: 1
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Activation request created successfully',
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
                            example: 'Your activation request has been submitted successfully. Please wait for admin approval.'
                        ),

                        new OA\Property(
                            property: 'status',
                            type: 'string',
                            example: 'pending'
                        )
                    ]
                )
            ),

            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function activate(
        ActivateAccountRequest $request
    ): JsonResponse {
        $this->authService->activate(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Your activation request has been submitted successfully. Please wait for admin approval.',
            'status' => 'pending',
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Student Login
    |--------------------------------------------------------------------------
    */

    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'studentLogin',
        summary: 'Student login',
        description: 'Logs in an approved student using their subscription code and automatically detects the device information from the request.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: [
                    'subscription_code',
                    'device_identifier'
                ],
                properties: [
                    new OA\Property(
                        property: 'subscription_code',
                        type: 'string',
                        example: 'A7K9P2X4LM'
                    ),

                    new OA\Property(
                        property: 'device_identifier',
                        type: 'string',
                        format: 'uuid',
                        example: '8f4c2e91-7a32-4d8b-91a2-5e6f3c8d1204',
                        description: 'Unique device identifier generated by the client application.'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful'
            ),

            new OA\Response(
                response: 401,
                description: 'Invalid subscription code'
            ),

            new OA\Response(
                response: 403,
                description: 'Subscription is not approved, active, or another device is already trusted'
            ),

            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function login(
        StudentLoginRequest $request
    ): JsonResponse {
        $data = $request->validated();

        $result = $this->authService->login(
            $data['subscription_code'],
            $data['device_identifier'],
            $request
        );

        return response()->json([
            'success' => true,

            'message' => 'Student logged in successfully.',

            'data' => [

                /*
                |--------------------------------------------------------------------------
                | Student
                |--------------------------------------------------------------------------
                */

                'student' => [
                    'id' => $result['student']->id,

                    'name' => trim(
                        $result['student']->first_name . ' ' .
                        $result['student']->last_name
                    ),
                ],

                /*
                |--------------------------------------------------------------------------
                | Subscription
                |--------------------------------------------------------------------------
                */

                'subscription' => [
                    'id' => $result['subscription']->id,

                    'status' => $result['subscription']->status,

                    'approval_status' =>
                        $result['subscription']->approval_status,
                ],

                /*
                |--------------------------------------------------------------------------
                | Trusted Device
                |--------------------------------------------------------------------------
                */

                'device' => [
                    'id' => $result['device']->id,

                    'device_name' =>
                        $result['device']->device_name,

                    'platform' =>
                        $result['device']->platform,

                    'browser' =>
                        $result['device']->browser,

                    'operating_system' =>
                        $result['device']->operating_system,

                    'is_trusted' =>
                        $result['device']->is_trusted,
                ],

                /*
                |--------------------------------------------------------------------------
                | Sanctum Token
                |--------------------------------------------------------------------------
                */

                'token' => $result['token'],
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Student Logout
    |--------------------------------------------------------------------------
    */

    #[OA\Post(
        path: '/api/v1/auth/logout',
        operationId: 'studentLogout',
        summary: 'Student logout',
        description: 'Logs out the authenticated student by deleting the current Sanctum token. The trusted device remains registered.',
        tags: ['Authentication'],
        security: [
            ['sanctum' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful'
            ),

            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            )
        ]
    )]
    public function logout(
        Request $request
    ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student logged out successfully.',
        ]);
    }
}