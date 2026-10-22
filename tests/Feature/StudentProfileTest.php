<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTrustedDevice;
use App\Models\Device;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Memo;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionCode;
use App\Models\Video;
use App\Models\VideoProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/student/profile');

        $response->assertStatus(401);
    }

    public function test_authenticated_student_can_retrieve_profile_with_real_metrics(): void
    {
        $grade = Grade::factory()->create(['name' => 'الصف الثالث الثانوي']);

        $student = Student::factory()->create([
            'first_name' => 'أحمد',
            'last_name' => 'محمد',
            'phone' => '01011112222',
            'parent_phone' => '01033334444',
            'grade_id' => $grade->id,
            'registered_at' => now()->subMonths(2),
        ]);

        Sanctum::actingAs($student, ['*'], 'sanctum');

        $subCode = SubscriptionCode::create([
            'code' => 'SUB-2026-XYZ',
            'is_used' => true,
            'used_by_student_id' => $student->id,
            'used_at' => now()->subMonths(1),
            'approval_status' => 'approved',
        ]);

        $subscription = Subscription::create([
            'student_id' => $student->id,
            'subscription_code_id' => $subCode->id,
            'start_date' => now()->subMonths(1),
            'end_date' => now()->addMonths(5),
            'status' => 'active',
            'approval_status' => 'approved',
            'approved_at' => now()->subMonths(1),
        ]);

        $device = Device::create([
            'subscription_id' => $subscription->id,
            'device_identifier' => 'test-device-uuid-1234',
            'device_name' => 'Chrome on Windows',
            'platform' => 'Windows',
            'browser' => 'Chrome',
            'operating_system' => 'Windows 11',
            'is_trusted' => true,
            'registered_at' => now(),
        ]);

        // Academic content: Section -> Lesson -> Video & Exam
        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'الوحدة الأولى',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'الدرس الأول',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $video1 = Video::create([
            'lesson_id' => $lesson->id,
            'title' => 'فيديو 1',
            'video_url' => 'https://youtube.com/watch?v=1',
            'sort_order' => 1,
            'duration_seconds' => 600,
            'completion_percentage' => 90,
            'is_active' => true,
        ]);

        $video2 = Video::create([
            'lesson_id' => $lesson->id,
            'title' => 'فيديو 2',
            'video_url' => 'https://youtube.com/watch?v=2',
            'sort_order' => 2,
            'duration_seconds' => 600,
            'completion_percentage' => 90,
            'is_active' => true,
        ]);

        // Student watched 1 video of 2 -> 50% attendance
        VideoProgress::create([
            'student_id' => $student->id,
            'video_id' => $video1->id,
            'subscription_id' => $subscription->id,
            'watched_seconds' => 600,
            'is_completed' => true,
        ]);

        $exam = Exam::create([
            'lesson_id' => $lesson->id,
            'title' => 'اختبار الدرس الأول',
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        // Passed exam with 90% score
        ExamAttempt::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'subscription_id' => $subscription->id,
            'score' => 90,
            'total_score' => 100,
            'percentage' => 90.00,
            'is_passed' => true,
            'started_at' => now()->subDay(),
            'submitted_at' => now()->subDay(),
        ]);

        // Memos for grade
        Memo::factory()->create([
            'grade_id' => $grade->id,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson('/api/v1/student/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student profile retrieved successfully.',
            ])
            // Student identity
            ->assertJsonPath('data.student.id', $student->id)
            ->assertJsonPath('data.student.first_name', 'أحمد')
            ->assertJsonPath('data.student.last_name', 'محمد')
            ->assertJsonPath('data.student.phone', '01011112222')
            ->assertJsonPath('data.student.parent_phone', '01033334444')
            ->assertJsonPath('data.student.governorate', null)
            ->assertJsonPath('data.student.grade_id', $grade->id)
            ->assertJsonPath('data.student.grade_name', 'الصف الثالث الثانوي')
            // Subscription
            ->assertJsonPath('data.subscription.code', 'SUB-2026-XYZ')
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.subscription.is_valid', true)
            // Trusted device
            ->assertJsonPath('data.trusted_device.is_registered', true)
            ->assertJsonPath('data.trusted_device.is_trusted', true)
            ->assertJsonPath('data.trusted_device.device_name', 'Chrome on Windows')
            // Academic performance
            ->assertJsonPath('data.academic_performance.attendance_percentage', '50%')
            ->assertJsonPath('data.academic_performance.passed_exams_count', 1)
            ->assertJsonPath('data.academic_performance.average_exam_score_percentage', '90%')
            ->assertJsonPath('data.academic_performance.available_memos_count', 1);
    }

    public function test_student_can_update_profile_information(): void
    {
        $grade = Grade::factory()->create();
        $student = Student::factory()->create([
            'first_name' => 'قديم',
            'last_name' => 'السابق',
            'parent_phone' => '01000000000',
            'grade_id' => $grade->id,
        ]);

        Sanctum::actingAs($student, ['*'], 'sanctum');

        $subscription = Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->putJson('/api/v1/student/profile', [
                'first_name' => 'محمود',
                'last_name' => 'الجديد',
                'parent_phone' => '01099998888',
                'grade_id' => 999, // Should be ignored and not changed
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.student.first_name', 'محمود')
            ->assertJsonPath('data.student.last_name', 'الجديد')
            ->assertJsonPath('data.student.parent_phone', '01099998888')
            ->assertJsonPath('data.student.grade_id', $grade->id);

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'first_name' => 'محمود',
            'last_name' => 'الجديد',
            'parent_phone' => '01099998888',
            'grade_id' => $grade->id, // intact
        ]);
    }
}
