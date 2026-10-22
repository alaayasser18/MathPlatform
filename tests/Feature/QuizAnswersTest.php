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

class QuizAnswersTest extends TestCase
{
    use RefreshDatabase;

    private Student $student;

    private Grade $grade;

    private Lesson $lesson;

    private Exam $exam;

    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->grade = Grade::factory()->create();
        $this->student = Student::factory()->create(['grade_id' => $this->grade->id]);

        $this->subscription = Subscription::create([
            'student_id' => $this->student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $section = Section::create([
            'grade_id' => $this->grade->id,
            'name' => 'الوحدة الأولى',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'المحددات',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار المحددات',
            'duration_minutes' => 15,
            'passing_percentage' => 50,
            'is_active' => true,
        ]);
    }

    public function test_submitting_exam_stores_answers_in_database(): void
    {
        Sanctum::actingAs($this->student, ['*'], 'sanctum');

        // Lesson video must be completed so exam is unlocked
        $video = Video::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'شرح الفيديو',
            'video_url' => 'https://www.youtube.com/watch?v=sample',
            'sort_order' => 1,
            'duration_seconds' => 100,
            'completion_percentage' => 90,
            'is_active' => true,
        ]);

        VideoProgress::create([
            'student_id' => $this->student->id,
            'video_id' => $video->id,
            'subscription_id' => $this->subscription->id,
            'watched_seconds' => 100,
            'is_completed' => true,
        ]);

        // Questions & options
        $q1 = $this->exam->questions()->create([
            'question_text' => 'ما هو ناتج 3 + 4؟',
            'points' => 2,
            'sort_order' => 1,
        ]);
        $opt1Correct = $q1->options()->create([
            'option_text' => '7',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        $opt1Wrong = $q1->options()->create([
            'option_text' => '8',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $q2 = $this->exam->questions()->create([
            'question_text' => 'ما هو ناتج 5 * 2؟',
            'points' => 3,
            'sort_order' => 2,
        ]);
        $opt2Correct = $q2->options()->create([
            'option_text' => '10',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        $opt2Wrong = $q2->options()->create([
            'option_text' => '15',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        // Create attempt
        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'subscription_id' => $this->subscription->id,
            'started_at' => now(),
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->postJson("/api/v1/student/exams/{$this->exam->id}/submit", [
                'attempt_id' => $attempt->id,
                'answers' => [
                    ['question_id' => $q1->id, 'option_id' => $opt1Correct->id], // correct
                    ['question_id' => $q2->id, 'option_id' => $opt2Wrong->id],   // wrong
                ],
            ]);

        $response->assertStatus(200);

        // Verify exam_attempt_answers table
        $this->assertDatabaseCount('exam_attempt_answers', 2);

        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q1->id,
            'option_id' => $opt1Correct->id,
            'is_correct' => true,
        ]);

        $this->assertDatabaseHas('exam_attempt_answers', [
            'exam_attempt_id' => $attempt->id,
            'question_id' => $q2->id,
            'option_id' => $opt2Wrong->id,
            'is_correct' => false,
        ]);
    }

    public function test_student_can_retrieve_quiz_answers_for_lesson(): void
    {
        Sanctum::actingAs($this->student, ['*'], 'sanctum');

        $q1 = $this->exam->questions()->create([
            'question_text' => 'سؤال رقم 1',
            'points' => 5,
            'sort_order' => 1,
        ]);
        $opt1Correct = $q1->options()->create(['option_text' => 'إجابة 1', 'is_correct' => true, 'sort_order' => 1]);
        $opt1Wrong = $q1->options()->create(['option_text' => 'إجابة 2', 'is_correct' => false, 'sort_order' => 2]);

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'subscription_id' => $this->subscription->id,
            'score' => 5,
            'total_score' => 5,
            'percentage' => 100,
            'is_passed' => true,
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
        ]);

        $attempt->answers()->create([
            'question_id' => $q1->id,
            'option_id' => $opt1Correct->id,
            'is_correct' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/lessons/{$this->lesson->id}/quiz/answers");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Quiz answers retrieved successfully.',
            ])
            ->assertJsonPath('data.lesson.id', $this->lesson->id)
            ->assertJsonPath('data.exam.id', $this->exam->id)
            ->assertJsonPath('data.attempt.id', $attempt->id)
            ->assertJsonPath('data.attempt.score', 5)
            ->assertJsonPath('data.answers.0.question_id', $q1->id)
            ->assertJsonPath('data.answers.0.is_answered', true)
            ->assertJsonPath('data.answers.0.is_correct', true)
            ->assertJsonPath('data.answers.0.selected_option.id', $opt1Correct->id)
            ->assertJsonPath('data.answers.0.correct_option.id', $opt1Correct->id);
    }

    public function test_admin_can_retrieve_student_quiz_answers_for_lesson(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $q1 = $this->exam->questions()->create([
            'question_text' => 'سؤال المشرف',
            'points' => 10,
            'sort_order' => 1,
        ]);
        $optCorrect = $q1->options()->create(['option_text' => 'صح', 'is_correct' => true, 'sort_order' => 1]);
        $optWrong = $q1->options()->create(['option_text' => 'خطأ', 'is_correct' => false, 'sort_order' => 2]);

        $attempt = ExamAttempt::create([
            'student_id' => $this->student->id,
            'exam_id' => $this->exam->id,
            'subscription_id' => $this->subscription->id,
            'score' => 0,
            'total_score' => 10,
            'percentage' => 0,
            'is_passed' => false,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
        ]);

        $attempt->answers()->create([
            'question_id' => $q1->id,
            'option_id' => $optWrong->id,
            'is_correct' => false,
        ]);

        $response = $this->getJson("/api/v1/admin/students/{$this->student->id}/lessons/{$this->lesson->id}/quiz/answers");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Student quiz answers retrieved successfully.',
            ])
            ->assertJsonPath('data.student.id', $this->student->id)
            ->assertJsonPath('data.lesson.id', $this->lesson->id)
            ->assertJsonPath('data.attempt.id', $attempt->id)
            ->assertJsonPath('data.answers.0.is_correct', false)
            ->assertJsonPath('data.answers.0.selected_option.id', $optWrong->id)
            ->assertJsonPath('data.answers.0.correct_option.id', $optCorrect->id);
    }
}
