<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Models\ExamAttempt;
use App\Models\Memo;
use App\Models\Student;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Services\Student\StudentProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class StudentProfileController extends Controller
{
    public function __construct(
        private StudentProfileService $studentProfileService
    ) {}

    #[OA\Get(
        path: '/api/v1/student/profile',
        operationId: 'getStudentProfile',
        summary: 'Get student profile',
        description: 'Returns the authenticated student profile, active subscription, grade, trusted device, and real academic metrics.',
        tags: ['Student Profile'],
        security: [
            ['sanctum' => []],
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
            ),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $result = $this->studentProfileService
            ->getProfile($student);

        $subscription = $result['subscription'];
        $device = $result['device'];

        $completedQuizzes = ExamAttempt::where('student_id', $student->id)
            ->where('is_passed', true)
            ->count();

        $avgScore = ExamAttempt::where('student_id', $student->id)
            ->whereNotNull('submitted_at')
            ->avg('percentage');
        $averageScore = $avgScore !== null ? round((float) $avgScore, 1).'%' : '0%';

        $memosCount = Memo::where('grade_id', $student->grade_id)
            ->where('is_active', true)
            ->count();

        $totalGradeVideos = Video::whereHas('lesson.section', function ($q) use ($student) {
            $q->where('grade_id', $student->grade_id)->where('is_active', true);
        })->where('is_active', true)->count();

        $completedVideos = VideoProgress::where('student_id', $student->id)
            ->where('is_completed', true)
            ->count();

        $attendanceRate = $totalGradeVideos > 0
            ? round(($completedVideos / $totalGradeVideos) * 100).'%'
            : '100%';

        $subscriptionCode = $subscription->subscriptionCode?->code ?? 'N/A';

        $validityText = 'مستمر حتى نهاية العام الدراسي';
        if ($subscription->end_date) {
            $validityText = $subscription->end_date->isPast()
                ? 'منتهي الصلاحية'
                : 'مستمر حتى '.$subscription->end_date->toDateString();
        }

        $regDate = $student->registered_at?->toDateString()
            ?? $student->created_at?->toDateString();

        $studentData = [
            'id' => $student->id,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'name' => trim($student->first_name.' '.$student->last_name),
            'phone' => $student->phone,
            'parent_phone' => $student->parent_phone,
            'governorate' => null,
            'grade_id' => $student->grade_id,
            'grade_name' => $student->grade?->name,
            'registration_date' => $regDate,
            'registered_at' => $regDate,
            'attendanceRate' => $attendanceRate,
            'completedQuizzes' => $completedQuizzes,
            'averageScore' => $averageScore,
            'grade' => $student->grade
                ? [
                    'id' => $student->grade->id,
                    'name' => $student->grade->name,
                ]
                : null,
        ];

        $subscriptionData = [
            'id' => $subscription->id,
            'code' => $subscriptionCode,
            'status' => $subscription->status,
            'approval_status' => $subscription->approval_status,
            'start_date' => $subscription->start_date?->toDateString(),
            'end_date' => $subscription->end_date?->toDateString(),
            'validity_text' => $validityText,
            'is_valid' => $subscription->status === 'active' && $subscription->approval_status === 'approved',
        ];

        $isTrusted = $device !== null && (bool) $device->is_trusted;
        $deviceData = [
            'id' => $device?->id,
            'is_registered' => $isTrusted,
            'is_trusted' => $isTrusted,
            'device_name' => $device?->device_name,
            'platform' => $device?->platform,
            'browser' => $device?->browser,
            'operating_system' => $device?->operating_system,
        ];

        $academicPerformanceData = [
            'attendance_percentage' => $attendanceRate,
            'attendance_rate' => $attendanceRate,
            'passed_exams_count' => $completedQuizzes,
            'completed_quizzes' => $completedQuizzes,
            'average_exam_score_percentage' => $averageScore,
            'average_score' => $averageScore,
            'available_memos_count' => $memosCount,
            'memos_count' => $memosCount,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Student profile retrieved successfully.',
            'data' => [
                'student' => $studentData,
                'subscription' => $subscriptionData,
                'trusted_device' => $deviceData,
                'device' => $deviceData,
                'academic_performance' => $academicPerformanceData,
                'stats' => $academicPerformanceData,
            ],
            'student' => $studentData,
            'subscription' => $subscriptionData,
            'trusted_device' => $deviceData,
            'academic_performance' => $academicPerformanceData,
        ]);
    }

    #[OA\Put(
        path: '/api/v1/student/profile',
        operationId: 'updateStudentProfile',
        summary: 'Update student profile',
        description: 'Allows authenticated student to update editable personal details (first_name, last_name, parent_phone).',
        tags: ['Student Profile'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'first_name', type: 'string', example: 'أحمد'),
                    new OA\Property(property: 'last_name', type: 'string', example: 'محمد'),
                    new OA\Property(property: 'parent_phone', type: 'string', example: '01012345678'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Profile updated successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var Student $student */
        $student = $request->user();

        $validated = $request->validated();

        $updateData = array_filter([
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'parent_phone' => $validated['parent_phone'] ?? null,
        ], fn ($val) => $val !== null);

        if (! empty($updateData)) {
            $student->update($updateData);
        }

        return $this->show($request);
    }
}
