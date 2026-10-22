<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Memo;
use App\Models\Student;
use App\Services\LearningProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

#[OA\Tag(
    name: 'Student Memos',
    description: 'Student memo (PDF booklet) endpoints'
)]
class StudentMemoController extends Controller
{
    public function __construct(
        private LearningProgressService $learningProgressService
    ) {}

    /**
     * Return all active memos for the authenticated student's grade.
     */
    #[OA\Get(
        path: '/api/v1/student/memos',
        operationId: 'studentMemosIndex',
        summary: 'Get memos for the student\'s grade',
        tags: ['Student Memos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Memos retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Student access required'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => true,
                'message' => 'No grade assigned to this student.',
                'data' => [
                    'memos' => [],
                ],
            ]);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => true,
                'message' => 'No active subscription found.',
                'data' => [
                    'memos' => [],
                ],
            ]);
        }

        $memos = Memo::query()
            ->where('grade_id', $student->grade_id)
            ->where('is_active', true)
            ->with('grade:id,name')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Memos retrieved successfully.',
            'data' => [
                'grade' => [
                    'id' => $student->grade->id,
                    'name' => $student->grade->name,
                ],
                'memos' => $memos,
            ],
        ]);
    }

    /**
     * Download the memo PDF booklet for the student.
     */
    #[OA\Get(
        path: '/api/v1/student/memos/{memo}/download',
        operationId: 'studentMemosDownload',
        summary: 'Download memo PDF booklet',
        tags: ['Student Memos'],
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
            new OA\Response(response: 200, description: 'PDF file download initiated'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'No active subscription or wrong grade'),
            new OA\Response(response: 404, description: 'Memo not found or file missing'),
        ]
    )]
    public function download(Request $request, int $memo): Response
    {
        /** @var Student $student */
        $student = $request->user();
        $student->loadMissing('grade');

        if (! $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'No grade assigned to this student.',
            ], 403);
        }

        $subscription = $this->learningProgressService
            ->getActiveSubscription($student->id);

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found. You must have an approved active subscription to download memos.',
            ], 403);
        }

        $memoModel = Memo::query()
            ->where('id', $memo)
            ->where('is_active', true)
            ->first();

        if (! $memoModel) {
            return response()->json([
                'success' => false,
                'message' => 'Memo not found.',
            ], 404);
        }

        if ($memoModel->grade_id !== $student->grade_id) {
            return response()->json([
                'success' => false,
                'message' => 'This memo belongs to another grade and is not accessible to your account.',
            ], 403);
        }

        if (empty($memoModel->file_url)) {
            return response()->json([
                'success' => false,
                'message' => 'No PDF file is currently attached to this memo.',
            ], 404);
        }

        $safeTitle = preg_replace('/[^\p{L}\p{N}\-_.]/u', '_', $memoModel->title);
        $fileName = ($safeTitle ?: 'memo_'.$memoModel->id).'.pdf';

        // Check if stored in local public disk
        if (str_contains($memoModel->file_url, '/storage/')) {
            $storagePath = str_replace('/storage/', '', $memoModel->file_url);
            if (Storage::disk('public')->exists($storagePath)) {
                return Storage::disk('public')->download($storagePath, $fileName, [
                    'Content-Type' => 'application/pdf',
                ]);
            }
        }

        // Check if full URL (external storage or CDN)
        if (filter_var($memoModel->file_url, FILTER_VALIDATE_URL)) {
            return redirect()->away($memoModel->file_url);
        }

        return response()->json([
            'success' => false,
            'message' => 'The PDF file could not be located on the server.',
        ], 404);
    }
}
