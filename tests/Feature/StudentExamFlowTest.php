<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTrustedDevice;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\Video;
use App\Models\VideoProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentExamFlowTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Grade $grade;

    private Student $student;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->grade = Grade::factory()->create();
        $this->student = Student::factory()->create(['grade_id' => $this->grade->id]);

        $this->subscription = Subscription::create([
            'student_id' => $this->student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);
    }

    public function test_admin_can_create_standalone_exam_without_lesson(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'grade_id' => $this->grade->id,
            'lesson_id' => null,
            'title' => 'امتحان شامل تجريبي',
            'duration_minutes' => 45,
            'passing_percentage' => 60,
            'questions' => [
                [
                    'question_text' => 'سؤال شامل 1',
                    'points' => 2,
                    'options' => [
                        ['option_text' => 'إجابة صحيحة', 'is_correct' => true],
                        ['option_text' => 'إجابة خاطئة', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/admin/exams', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exam.title', 'امتحان شامل تجريبي')
            ->assertJsonPath('data.exam.grade_id', $this->grade->id)
            ->assertJsonPath('data.exam.lesson_id', null);

        $this->assertDatabaseHas('exams', [
            'title' => 'امتحان شامل تجريبي',
            'grade_id' => $this->grade->id,
            'lesson_id' => null,
        ]);
    }

    public function test_student_can_list_and_access_standalone_exam_without_lesson_video(): void
    {
        Sanctum::actingAs($this->student, ['*'], 'sanctum');

        $standaloneExam = Exam::create([
            'grade_id' => $this->grade->id,
            'lesson_id' => null,
            'title' => 'امتحان نهاية الشهر',
            'duration_minutes' => 30,
            'passing_percentage' => 50,
            'is_active' => true,
        ]);

        $q = $standaloneExam->questions()->create([
            'question_text' => 'ما هو ناتج 2 * 3؟',
            'points' => 1,
            'sort_order' => 1,
        ]);
        $q->options()->create(['option_text' => '6', 'is_correct' => true, 'sort_order' => 1]);
        $q->options()->create(['option_text' => '5', 'is_correct' => false, 'sort_order' => 2]);

        $listResponse = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson('/api/v1/student/exams');

        $listResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exams.0.id', $standaloneExam->id)
            ->assertJsonPath('data.exams.0.is_standalone', true)
            ->assertJsonPath('data.exams.0.is_unlocked', true);

        $showResponse = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$standaloneExam->id}");

        $showResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exam.id', $standaloneExam->id);

        $startResponse = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->postJson("/api/v1/student/exams/{$standaloneExam->id}/start");

        $startResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['attempt_id', 'started_at']]);
    }

    public function test_lesson_exam_flow_with_prerequisites_and_video_completion(): void
    {
        Sanctum::actingAs($this->student, ['*'], 'sanctum');

        $section = Section::create([
            'grade_id' => $this->grade->id,
            'name' => 'الوحدة الأولى',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Lesson 1
        $lesson1 = Lesson::create([
            'section_id' => $section->id,
            'title' => 'الدرس الأول',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $video1 = Video::create([
            'lesson_id' => $lesson1->id,
            'title' => 'فيديو الدرس الأول',
            'video_url' => 'https://example.com/v1',
            'sort_order' => 1,
            'duration_seconds' => 100,
            'completion_percentage' => 90,
            'is_active' => true,
        ]);

        $exam1 = Exam::create([
            'lesson_id' => $lesson1->id,
            'title' => 'امتحان الدرس الأول',
            'duration_minutes' => 10,
            'passing_percentage' => 50,
            'is_active' => true,
        ]);

        $q1 = $exam1->questions()->create([
            'question_text' => 'سؤال 1',
            'points' => 2,
            'sort_order' => 1,
        ]);
        $opt1 = $q1->options()->create(['option_text' => 'صح', 'is_correct' => true, 'sort_order' => 1]);

        // Lesson 2
        $lesson2 = Lesson::create([
            'section_id' => $section->id,
            'title' => 'الدرس الثاني',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $video2 = Video::create([
            'lesson_id' => $lesson2->id,
            'title' => 'فيديو الدرس الثاني',
            'video_url' => 'https://example.com/v2',
            'sort_order' => 1,
            'duration_seconds' => 120,
            'completion_percentage' => 90,
            'is_active' => true,
        ]);

        $exam2 = Exam::create([
            'lesson_id' => $lesson2->id,
            'title' => 'امتحان الدرس الثاني',
            'duration_minutes' => 15,
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        $q2 = $exam2->questions()->create([
            'question_text' => 'سؤال 2',
            'points' => 5,
            'sort_order' => 1,
        ]);
        $opt2 = $q2->options()->create(['option_text' => 'نعم', 'is_correct' => true, 'sort_order' => 1]);

        // 1. Exam 1 is initially locked because Video 1 is not completed
        $responseExam1Locked = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam1->id}");

        $responseExam1Locked->assertStatus(403)
            ->assertJsonPath('message', 'This exam is locked. Complete all lesson videos first.');

        // 2. Complete Video 1
        VideoProgress::create([
            'student_id' => $this->student->id,
            'subscription_id' => $this->subscription->id,
            'video_id' => $video1->id,
            'completion_percentage' => 95,
            'is_completed' => true,
        ]);

        // 3. Exam 1 is now unlocked!
        $responseExam1Unlocked = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam1->id}");
        $responseExam1Unlocked->assertStatus(200);

        // 4. Exam 2 is locked because Lesson 1's exam hasn't been passed yet
        $responseExam2PreviousLocked = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam2->id}");

        $responseExam2PreviousLocked->assertStatus(403)
            ->assertJsonPath('message', 'This exam is locked. Complete previous lessons and exams first.');

        // 5. Pass Exam 1
        $attempt1 = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $exam1->id,
            'subscription_id' => $this->subscription->id,
            'started_at' => now(),
        ]);

        $submitExam1 = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->postJson("/api/v1/student/exams/{$exam1->id}/submit", [
                'attempt_id' => $attempt1->id,
                'answers' => [
                    ['question_id' => $q1->id, 'option_id' => $opt1->id],
                ],
            ]);
        $submitExam1->assertStatus(200)
            ->assertJsonPath('data.is_passed', true);

        // 6. Exam 2 is now unlocked for previous lesson, BUT locked because Video 2 is not completed!
        $responseExam2VideoLocked = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam2->id}");

        $responseExam2VideoLocked->assertStatus(403)
            ->assertJsonPath('message', 'This exam is locked. Complete all lesson videos first.');

        // 7. Complete Video 2
        VideoProgress::create([
            'student_id' => $this->student->id,
            'subscription_id' => $this->subscription->id,
            'video_id' => $video2->id,
            'completion_percentage' => 100,
            'is_completed' => true,
        ]);

        // 8. Exam 2 is NOW FULLY UNLOCKED!
        $responseExam2Unlocked = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam2->id}");

        $responseExam2Unlocked->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exam.id', $exam2->id);

        // 9. Student can start Exam 2
        $startExam2 = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->postJson("/api/v1/student/exams/{$exam2->id}/start");

        $startExam2->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
