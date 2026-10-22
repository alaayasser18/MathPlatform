<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMemoRequest;
use App\Http\Requests\Admin\UpdateMemoRequest;
use App\Models\Memo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Admin Memos',
    description: 'Admin memo (PDF booklet) management endpoints'
)]
class AdminMemoController extends Controller
{
    /**
     * List all memos, optionally filtered by grade.
     */
    #[OA\Get(
        path: '/api/v1/admin/memos',
        operationId: 'adminMemosIndex',
        summary: 'Get all memos',
        tags: ['Admin Memos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'grade_id',
                description: 'Filter memos by grade ID',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Memos retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Memo::query()
            ->with('grade:id,name')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($request->filled('grade_id')) {
            $query->where('grade_id', $request->integer('grade_id'));
        }

        $memos = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Memos retrieved successfully.',
            'data' => [
                'memos' => $memos,
            ],
        ]);
    }

    /**
     * Create a new memo with optional PDF and cover image upload.
     */
    #[OA\Post(
        path: '/api/v1/admin/memos',
        operationId: 'adminMemosStore',
        summary: 'Create a new memo (supports PDF file upload)',
        tags: ['Admin Memos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['grade_id', 'title'],
                        properties: [
                            new OA\Property(property: 'grade_id', type: 'integer', example: 1),
                            new OA\Property(property: 'title', type: 'string', example: 'مذكرة الجبر للصف الثالث الثانوي'),
                            new OA\Property(property: 'description', type: 'string', nullable: true),
                            new OA\Property(property: 'pdf_file', type: 'string', format: 'binary', description: 'PDF file to upload', nullable: true),
                            new OA\Property(property: 'file_url', type: 'string', nullable: true),
                            new OA\Property(property: 'cover_image', type: 'string', format: 'binary', description: 'Cover image file', nullable: true),
                            new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
                            new OA\Property(property: 'file_size', type: 'string', example: '18.4 MB', nullable: true),
                            new OA\Property(property: 'pages', type: 'integer', example: 140, nullable: true),
                            new OA\Property(property: 'year', type: 'string', example: '2026 / 2027', nullable: true),
                            new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                            new OA\Property(property: 'sort_order', type: 'integer', example: 0, nullable: true),
                            new OA\Property(property: 'is_active', type: 'boolean', example: true, nullable: true),
                        ]
                    )
                ),
                new OA\JsonContent(
                    required: ['grade_id', 'title'],
                    properties: [
                        new OA\Property(property: 'grade_id', type: 'integer', example: 1),
                        new OA\Property(property: 'title', type: 'string', example: 'مذكرة الجبر للصف الثالث الثانوي'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'file_url', type: 'string', nullable: true),
                        new OA\Property(property: 'file_size', type: 'string', example: '18.4 MB', nullable: true),
                        new OA\Property(property: 'pages', type: 'integer', example: 140, nullable: true),
                        new OA\Property(property: 'year', type: 'string', example: '2026 / 2027', nullable: true),
                        new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
                        new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'sort_order', type: 'integer', example: 0, nullable: true),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Memo created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function store(StoreMemoRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('pdf_file')) {
            $pdfFile = $request->file('pdf_file');
            $path = $pdfFile->store('memos/pdfs', 'public');
            $data['file_url'] = Storage::url($path);

            if (empty($data['file_size'])) {
                $bytes = $pdfFile->getSize();
                $data['file_size'] = round($bytes / (1024 * 1024), 1).' MB';
            }
        }

        if ($request->hasFile('cover_image')) {
            $coverFile = $request->file('cover_image');
            $coverPath = $coverFile->store('memos/covers', 'public');
            $data['cover_image_url'] = Storage::url($coverPath);
        }

        unset($data['pdf_file'], $data['cover_image']);

        if (! isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $memo = Memo::create($data);
        $memo->load('grade:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Memo created successfully.',
            'data' => [
                'memo' => $memo,
            ],
        ], 201);
    }

    /**
     * Show a single memo.
     */
    #[OA\Get(
        path: '/api/v1/admin/memos/{memo}',
        operationId: 'adminMemosShow',
        summary: 'Get a memo',
        tags: ['Admin Memos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'memo',
                description: 'Memo ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Memo retrieved successfully'),
            new OA\Response(response: 404, description: 'Memo not found'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(int $memo): JsonResponse
    {
        $memoModel = Memo::with('grade:id,name')->find($memo);

        if (! $memoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Memo not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Memo retrieved successfully.',
            'data' => [
                'memo' => $memoModel,
            ],
        ]);
    }

    /**
     * Update an existing memo (supports PDF file and cover image replacement).
     */
    #[OA\Put(
        path: '/api/v1/admin/memos/{memo}',
        operationId: 'adminMemosUpdate',
        summary: 'Update a memo',
        tags: ['Admin Memos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'memo',
                description: 'Memo ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'grade_id', type: 'integer', nullable: true),
                            new OA\Property(property: 'title', type: 'string', nullable: true),
                            new OA\Property(property: 'description', type: 'string', nullable: true),
                            new OA\Property(property: 'pdf_file', type: 'string', format: 'binary', nullable: true),
                            new OA\Property(property: 'file_url', type: 'string', nullable: true),
                            new OA\Property(property: 'cover_image', type: 'string', format: 'binary', nullable: true),
                            new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
                            new OA\Property(property: 'file_size', type: 'string', nullable: true),
                            new OA\Property(property: 'pages', type: 'integer', nullable: true),
                            new OA\Property(property: 'year', type: 'string', nullable: true),
                            new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                            new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                            new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                        ]
                    )
                ),
                new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'grade_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'title', type: 'string', nullable: true),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'file_url', type: 'string', nullable: true),
                        new OA\Property(property: 'file_size', type: 'string', nullable: true),
                        new OA\Property(property: 'pages', type: 'integer', nullable: true),
                        new OA\Property(property: 'year', type: 'string', nullable: true),
                        new OA\Property(property: 'cover_image_url', type: 'string', nullable: true),
                        new OA\Property(property: 'features', type: 'array', items: new OA\Items(type: 'string'), nullable: true),
                        new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean', nullable: true),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Memo updated successfully'),
            new OA\Response(response: 404, description: 'Memo not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function update(UpdateMemoRequest $request, int $memo): JsonResponse
    {
        $memoModel = Memo::find($memo);

        if (! $memoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Memo not found.',
            ], 404);
        }

        $data = $request->validated();

        if ($request->hasFile('pdf_file')) {
            // Delete old file if stored locally
            if ($memoModel->file_url && str_contains($memoModel->file_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $memoModel->file_url);
                Storage::disk('public')->delete($oldPath);
            }

            $pdfFile = $request->file('pdf_file');
            $path = $pdfFile->store('memos/pdfs', 'public');
            $data['file_url'] = Storage::url($path);

            if (empty($data['file_size'])) {
                $bytes = $pdfFile->getSize();
                $data['file_size'] = round($bytes / (1024 * 1024), 1).' MB';
            }
        }

        if ($request->hasFile('cover_image')) {
            if ($memoModel->cover_image_url && str_contains($memoModel->cover_image_url, '/storage/')) {
                $oldCover = str_replace('/storage/', '', $memoModel->cover_image_url);
                Storage::disk('public')->delete($oldCover);
            }

            $coverFile = $request->file('cover_image');
            $coverPath = $coverFile->store('memos/covers', 'public');
            $data['cover_image_url'] = Storage::url($coverPath);
        }

        unset($data['pdf_file'], $data['cover_image']);

        $memoModel->update($data);
        $memoModel->load('grade:id,name');

        return response()->json([
            'success' => true,
            'message' => 'Memo updated successfully.',
            'data' => [
                'memo' => $memoModel,
            ],
        ]);
    }

    /**
     * Delete a memo and its associated files.
     */
    #[OA\Delete(
        path: '/api/v1/admin/memos/{memo}',
        operationId: 'adminMemosDestroy',
        summary: 'Delete a memo',
        tags: ['Admin Memos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'memo',
                description: 'Memo ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Memo deleted successfully'),
            new OA\Response(response: 404, description: 'Memo not found'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function destroy(int $memo): JsonResponse
    {
        $memoModel = Memo::find($memo);

        if (! $memoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Memo not found.',
            ], 404);
        }

        if ($memoModel->file_url && str_contains($memoModel->file_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $memoModel->file_url);
            Storage::disk('public')->delete($oldPath);
        }

        if ($memoModel->cover_image_url && str_contains($memoModel->cover_image_url, '/storage/')) {
            $oldCover = str_replace('/storage/', '', $memoModel->cover_image_url);
            Storage::disk('public')->delete($oldCover);
        }

        $memoModel->delete();

        return response()->json([
            'success' => true,
            'message' => 'Memo deleted successfully.',
        ]);
    }
}
