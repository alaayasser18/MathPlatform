<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Http\Resources\Admin\StudentResource;
use App\Models\Admin;
use App\Models\Student;
use App\Services\Admin\AdminStudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminStudentController extends Controller
{
    public function __construct(
        private AdminStudentService $adminStudentService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/students',
        operationId: 'getAllStudents',
        summary: 'Get all students',
        description: 'Returns a paginated list of all students.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1
                ),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Students retrieved successfully'
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
    public function index(Request $request)
    {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $students = $this->adminStudentService
            ->getAllStudents();

        return StudentResource::collection($students);
    }

    #[OA\Get(
        path: '/api/v1/admin/students/{student}',
        operationId: 'getStudentByAdmin',
        summary: 'Get student details',
        description: 'Returns the details of a specific student.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Student retrieved successfully'
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
                description: 'Student not found'
            )
        ]
    )]
    public function show(
        Request $request,
        Student $student
    ) {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->getStudent($student);

        return new StudentResource($student);
    }

    #[OA\Post(
        path: '/api/v1/admin/students',
        operationId: 'createStudentByAdmin',
        summary: 'Create student manually by admin',
        description: 'Creates a student, enrollment, active subscription, and an approved subscription code. No approval is required when the student is created by an admin.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []]
        ],
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
                    ),
                    new OA\Property(
                        property: 'start_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2026-09-18'
                    ),
                    new OA\Property(
                        property: 'end_date',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2027-09-18'
                    ),
                    new OA\Property(
                        property: 'code_expires_at',
                        type: 'string',
                        format: 'date',
                        nullable: true,
                        example: '2027-09-18'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Student created successfully'
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
                description: 'Validation error'
            )
        ]
    )]
    public function store(
        Request $request,
        CreateStudentRequest $createStudentRequest
    ): JsonResponse|StudentResource {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->createStudent(
                $createStudentRequest->validated()
            );

        return (new StudentResource($student))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Put(
        path: '/api/v1/admin/students/{student}',
        operationId: 'updateStudentByAdmin',
        summary: 'Update student',
        description: 'Updates the basic information of a student.',
        tags: ['Admin Students'],
        security: [
            ['sanctum' => []]
        ],
        parameters: [
            new OA\Parameter(
                name: 'student',
                in: 'path',
                required: true,
                schema: new OA\Schema(
                    type: 'integer'
                ),
                example: 1
            )
        ],
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
                response: 200,
                description: 'Student updated successfully'
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
                description: 'Student not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function update(
        Request $request,
        UpdateStudentRequest $updateStudentRequest,
        Student $student
    ): JsonResponse|StudentResource {
        if (!$request->user() instanceof Admin) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
            ], 403);
        }

        $student = $this->adminStudentService
            ->updateStudent(
                $student,
                $updateStudentRequest->validated()
            );

        return new StudentResource($student);
    }
}