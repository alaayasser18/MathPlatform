<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Exam;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Option;
use App\Models\Question;
use App\Models\Section;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminExamTest extends TestCase
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
            'title' => 'المصفوفات',
            'sort_order' => 1,
            'is_active' => true,
        ]);
    }

    public function test_can_list_exams_with_accurate_questions_count(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار الدرس الأول',
            'description' => 'شرح الاختبار',
            'duration_minutes' => 20,
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        $exam->questions()->create([
            'question_text' => 'سؤال 1',
            'points' => 1,
            'sort_order' => 1,
        ]);

        $response = $this->getJson('/api/v1/admin/exams');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.exams')
            ->assertJsonPath('data.exams.0.title', 'اختبار الدرس الأول')
            ->assertJsonPath('data.exams.0.questions_count', 1);
    }

    public function test_can_create_exam_with_questions_at_once(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $payload = [
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار مصفوفات مع أسئلة',
            'passing_percentage' => 60,
            'questions' => [
                [
                    'question_text' => 'ما هو ناتج 1 + 1؟',
                    'points' => 1,
                    'options' => [
                        ['option_text' => '2', 'is_correct' => true],
                        ['option_text' => '3', 'is_correct' => false],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/admin/exams', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.exam.questions_count', 1)
            ->assertJsonPath('data.exam.questions.0.question_text', 'ما هو ناتج 1 + 1؟')
            ->assertJsonPath('data.exam.questions.0.options.0.option_text', '2');

        $this->assertDatabaseHas('questions', [
            'question_text' => 'ما هو ناتج 1 + 1؟',
        ]);
        $this->assertDatabaseHas('options', [
            'option_text' => '2',
            'is_correct' => true,
        ]);
    }

    public function test_can_add_question_with_options_directly_to_exam(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار تجريبي',
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/admin/exams/{$exam->id}/questions", [
            'question_text' => 'سؤال مباشر مع خيارات',
            'points' => 2,
            'options' => [
                ['option_text' => 'خيار أ', 'is_correct' => true],
                ['option_text' => 'خيار ب', 'is_correct' => false],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.question.question_text', 'سؤال مباشر مع خيارات')
            ->assertJsonPath('data.question.options.0.option_text', 'خيار أ')
            ->assertJsonPath('data.questions_count', 1);

        $this->assertDatabaseHas('questions', [
            'exam_id' => $exam->id,
            'question_text' => 'سؤال مباشر مع خيارات',
        ]);
        $this->assertDatabaseHas('options', [
            'option_text' => 'خيار أ',
            'is_correct' => true,
        ]);
        $this->assertDatabaseHas('options', [
            'option_text' => 'خيار ب',
            'is_correct' => false,
        ]);
    }

    public function test_can_create_exam_without_passing_percentage(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/exams', [
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار بدون نسبة نجاح',
            'passing_percentage' => null,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.exam.passing_percentage', null);

        $this->assertDatabaseHas('exams', [
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار بدون نسبة نجاح',
            'passing_percentage' => null,
        ]);
    }

    public function test_can_add_question_without_points_and_defaults_to_one(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار نقاط افتراضية',
            'passing_percentage' => 50,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/admin/exams/{$exam->id}/questions", [
            'question_text' => 'سؤال بدون نقاط محددة',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.question.points', 1);

        $this->assertDatabaseHas('questions', [
            'exam_id' => $exam->id,
            'question_text' => 'سؤال بدون نقاط محددة',
            'points' => 1,
        ]);
    }

    public function test_can_add_multiple_questions_to_exam_at_once(): void
    {
        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $exam = Exam::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'اختبار متعدد الأسئلة',
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/admin/exams/{$exam->id}/questions", [
            'questions' => [
                [
                    'question_text' => 'السؤال الأول',
                    'points' => 1,
                    'options' => [
                        ['option_text' => 'صح', 'is_correct' => true],
                        ['option_text' => 'خطأ', 'is_correct' => false],
                    ],
                ],
                [
                    'question_text' => 'السؤال الثاني',
                    'points' => 2,
                    'options' => [
                        ['option_text' => 'نعم', 'is_correct' => true],
                        ['option_text' => 'لا', 'is_correct' => false],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.questions_count', 2)
            ->assertJsonCount(2, 'data.questions');

        $this->assertDatabaseCount('questions', 2);
        $this->assertDatabaseCount('options', 4);
    }
}
