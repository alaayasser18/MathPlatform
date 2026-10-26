<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminLessonQuizTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    private Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();

        $grade = Grade::factory()->create();
        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'الجبر',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $this->lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'الجبر الخطي',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_unauthenticated_user_cannot_save_quiz_questions(): void
    {
        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", [
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 2 + 2؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '4', 'is_correct' => true],
                        ['option_text' => '5', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_returns_404_if_lesson_does_not_exist(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/lessons/99999/quiz/questions', [
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 2 + 2؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '4', 'is_correct' => true],
                        ['option_text' => '5', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Lesson not found.',
            ]);
    }

    public function test_validation_fails_when_questions_are_missing(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['questions']);
    }

    public function test_validation_fails_when_question_has_no_correct_option(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", [
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 2 + 2؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '3', 'is_correct' => false],
                        ['option_text' => '5', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['questions.0.options']);
    }

    public function test_validation_fails_when_question_has_multiple_correct_options(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", [
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 2 + 2؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '4', 'is_correct' => true],
                        ['option_text' => 'أربعة', 'is_correct' => true],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['questions.0.options']);
    }

    public function test_admin_can_save_quiz_questions_and_auto_create_exam(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'title' => 'اختبار الجبر الخطي',
            'duration_minutes' => 30,
            'passing_percentage' => 70,
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 5 * 5؟',
                    'points' => 2,
                    'options' => [
                        ['option_text' => '25', 'is_correct' => true],
                        ['option_text' => '20', 'is_correct' => false],
                        ['option_text' => '30', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'هل الصفر عدد زوجي؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => 'نعم', 'is_correct' => true],
                        ['option_text' => 'لا', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Quiz questions saved successfully.',
                'data' => [
                    'questions_count' => 2,
                ],
            ])
            ->assertJsonPath('data.exam.title', 'اختبار الجبر الخطي')
            ->assertJsonPath('data.exam.duration_minutes', 30)
            ->assertJsonPath('data.exam.passing_percentage', 70);

        $this->assertDatabaseHas('exams', [
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار الجبر الخطي',
            'duration_minutes' => 30,
            'passing_percentage' => 70,
        ]);

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('options', 5);
    }

    public function test_admin_can_append_questions_to_existing_quiz(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار مسبق',
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        $firstQuestion = $exam->questions()->create([
            'question_text' => 'السؤال الأول',
            'points' => 1,
            'sort_order' => 1,
        ]);
        $firstQuestion->options()->create([
            'option_text' => 'أ',
            'is_correct' => true,
            'sort_order' => 1,
        ]);
        $firstQuestion->options()->create([
            'option_text' => 'ب',
            'is_correct' => false,
            'sort_order' => 2,
        ]);

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", [
            'questions' => [
                [
                    'question_text' => 'السؤال الثاني',
                    'points' => 2,
                    'options' => [
                        ['option_text' => 'ج', 'is_correct' => true],
                        ['option_text' => 'د', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.questions_count', 2);

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('options', 4);
    }

    public function test_admin_can_save_single_question_payload_format(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson("/api/v1/admin/lessons/{$this->lesson->id}/quiz/questions", [
            'question_text' => 'سؤال منفرد',
            'points' => 3,
            'options' => [
                ['option_text' => 'إجابة صحيحة', 'is_correct' => true],
                ['option_text' => 'إجابة خاطئة', 'is_correct' => false],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Quiz questions saved successfully.',
            ])
            ->assertJsonPath('data.questions_count', 1);

        $this->assertDatabaseHas('questions', [
            'question_text' => 'سؤال منفرد',
            'points' => 3,
        ]);
    }

    public function test_admin_can_create_lesson_with_questions_directly(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'grade_id' => $this->lesson->section->grade_id,
            'section_name' => 'الجبر',
            'title' => 'المحددات',
            'video_url' => 'https://www.youtube.com/watch?v=12345678901',
            'quiz_title' => 'اختبار درس المحددات',
            'duration_minutes' => 20,
            'passing_percentage' => 70,
            'questions' => [
                [
                    'question_text' => 'ما هي قيمة محدد الوحدة؟',
                    'points' => 2,
                    'options' => [
                        ['option_text' => '1', 'is_correct' => true],
                        ['option_text' => '0', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/admin/lessons', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lesson.exam.title', 'اختبار درس المحددات')
            ->assertJsonPath('data.lesson.exam.questions.0.question_text', 'ما هي قيمة محدد الوحدة؟')
            ->assertJsonPath('data.lesson.exam.questions.0.options.0.option_text', '1');

        $lessonId = $response->json('data.lesson.id');
        $examId = $response->json('data.lesson.exam.id');

        // Check single lesson get endpoint
        $showResponse = $this->getJson("/api/v1/admin/lessons/{$lessonId}");
        $showResponse->assertStatus(200)
            ->assertJsonPath('data.lesson.exam.questions.0.question_text', 'ما هي قيمة محدد الوحدة؟');

        // Check lessons list eager loads questions and options
        $listResponse = $this->getJson('/api/v1/admin/lessons');
        $listResponse->assertStatus(200)
            ->assertJsonPath('data.lessons.1.exam.questions.0.question_text', 'ما هي قيمة محدد الوحدة؟');

        // Check exams index also shows questions
        $examListResponse = $this->getJson('/api/v1/admin/exams');
        $examListResponse->assertStatus(200)
            ->assertJsonPath('data.exams.0.questions.0.question_text', 'ما هي قيمة محدد الوحدة؟');
    }
}
