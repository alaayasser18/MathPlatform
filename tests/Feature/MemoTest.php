<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureTrustedDevice;
use App\Models\Admin;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\Lesson;
use App\Models\Material;
use App\Models\Memo;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MemoTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Admin Memo Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_admin_memos(): void
    {
        $response = $this->getJson('/api/v1/admin/memos');

        $response->assertStatus(401);
    }

    public function test_admin_can_list_all_memos(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $grade = Grade::factory()->create();
        Memo::factory()->count(3)->create(['grade_id' => $grade->id]);

        $response = $this->getJson('/api/v1/admin/memos');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Memos retrieved successfully.',
            ])
            ->assertJsonCount(3, 'data.memos');
    }

    public function test_admin_can_filter_memos_by_grade(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $grade1 = Grade::factory()->create(['name' => 'Grade 1']);
        $grade2 = Grade::factory()->create(['name' => 'Grade 2']);

        Memo::factory()->count(2)->create(['grade_id' => $grade1->id]);
        Memo::factory()->count(3)->create(['grade_id' => $grade2->id]);

        $response = $this->getJson("/api/v1/admin/memos?grade_id={$grade1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.memos');
    }

    public function test_admin_can_create_memo(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $grade = Grade::factory()->create();

        $payload = [
            'grade_id' => $grade->id,
            'title' => 'مذكرة الجبر',
            'description' => 'مذكرة شاملة لفرع الجبر',
            'file_url' => 'https://example.com/algebra.pdf',
            'file_size' => '3.5 MB',
            'pages' => 50,
            'year' => '2024 / 2025',
            'cover_image_url' => 'https://example.com/cover.jpg',
            'features' => ['شرح كامل', 'تمارين مع الحل'],
            'sort_order' => 1,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/admin/memos', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Memo created successfully.',
                'data' => [
                    'memo' => [
                        'title' => 'مذكرة الجبر',
                        'grade_id' => $grade->id,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('memos', [
            'title' => 'مذكرة الجبر',
            'grade_id' => $grade->id,
        ]);
    }

    public function test_admin_can_create_memo_with_uploaded_pdf_file(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $grade = Grade::factory()->create();
        $pdfFile = UploadedFile::fake()->create('algebra_booklet.pdf', 2048, 'application/pdf');

        $payload = [
            'grade_id' => $grade->id,
            'title' => 'مذكرة التفاضل المرفوعة',
            'pdf_file' => $pdfFile,
            'pages' => 80,
            'year' => '2026 / 2027',
        ];

        $response = $this->postJson('/api/v1/admin/memos', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Memo created successfully.',
            ]);

        $createdMemo = Memo::where('title', 'مذكرة التفاضل المرفوعة')->first();
        $this->assertNotNull($createdMemo);
        $this->assertStringContainsString('/storage/memos/pdfs/', $createdMemo->file_url);
        $this->assertNotNull($createdMemo->file_size);

        $storedPath = str_replace('/storage/', '', $createdMemo->file_url);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_admin_cannot_create_memo_with_invalid_data(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $response = $this->postJson('/api/v1/admin/memos', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['grade_id', 'title']);
    }

    public function test_admin_can_show_memo(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $memo = Memo::factory()->create();

        $response = $this->getJson("/api/v1/admin/memos/{$memo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Memo retrieved successfully.',
                'data' => [
                    'memo' => [
                        'id' => $memo->id,
                        'title' => $memo->title,
                    ],
                ],
            ]);
    }

    public function test_admin_can_update_memo(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $memo = Memo::factory()->create(['title' => 'Old Title']);

        $response = $this->putJson("/api/v1/admin/memos/{$memo->id}", [
            'title' => 'Updated Title',
            'pages' => 99,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Memo updated successfully.',
                'data' => [
                    'memo' => [
                        'id' => $memo->id,
                        'title' => 'Updated Title',
                        'pages' => 99,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('memos', [
            'id' => $memo->id,
            'title' => 'Updated Title',
            'pages' => 99,
        ]);
    }

    public function test_admin_can_update_memo_with_new_uploaded_pdf_file(): void
    {
        Storage::fake('public');

        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $oldPdf = UploadedFile::fake()->create('old.pdf', 1000, 'application/pdf');
        $oldPath = $oldPdf->store('memos/pdfs', 'public');

        $memo = Memo::factory()->create([
            'file_url' => Storage::url($oldPath),
        ]);

        $newPdf = UploadedFile::fake()->create('new_replacement.pdf', 3000, 'application/pdf');

        $response = $this->postJson("/api/v1/admin/memos/{$memo->id}", [
            'title' => 'Updated Memo Title',
            'pdf_file' => $newPdf,
        ]);

        $response->assertStatus(200);

        $memo->refresh();
        $this->assertEquals('Updated Memo Title', $memo->title);

        $newStoredPath = str_replace('/storage/', '', $memo->file_url);
        Storage::disk('public')->assertExists($newStoredPath);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_admin_can_delete_memo(): void
    {
        $admin = Admin::factory()->create();
        Sanctum::actingAs($admin, ['*'], 'sanctum');

        $memo = Memo::factory()->create();

        $response = $this->deleteJson("/api/v1/admin/memos/{$memo->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Memo deleted successfully.',
            ]);

        $this->assertDatabaseMissing('memos', [
            'id' => $memo->id,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Student Memo Tests
    |--------------------------------------------------------------------------
    */

    public function test_unauthenticated_user_cannot_access_student_memos(): void
    {
        $response = $this->getJson('/api/v1/student/memos');

        $response->assertStatus(401);
    }

    public function test_student_without_subscription_receives_empty_memos(): void
    {
        $grade = Grade::factory()->create();
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson('/api/v1/student/memos');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'No active subscription found.',
                'data' => [
                    'memos' => [],
                ],
            ]);
    }

    public function test_student_with_active_subscription_can_list_memos_for_their_grade(): void
    {
        $grade1 = Grade::factory()->create(['name' => 'First Secondary Test']);
        $grade2 = Grade::factory()->create(['name' => 'Second Secondary Test']);

        $student = Student::factory()->create(['grade_id' => $grade1->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $activeMemoGrade1 = Memo::factory()->create([
            'grade_id' => $grade1->id,
            'is_active' => true,
            'title' => 'Grade 1 Active Memo',
        ]);
        Memo::factory()->create([
            'grade_id' => $grade1->id,
            'is_active' => false,
            'title' => 'Grade 1 Inactive Memo',
        ]);

        Memo::factory()->create([
            'grade_id' => $grade2->id,
            'is_active' => true,
            'title' => 'Grade 2 Active Memo',
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson('/api/v1/student/memos');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Memos retrieved successfully.',
            ])
            ->assertJsonCount(1, 'data.memos')
            ->assertJsonPath('data.memos.0.id', $activeMemoGrade1->id);
    }

    public function test_student_with_active_subscription_can_download_memo(): void
    {
        Storage::fake('public');

        $grade = Grade::factory()->create(['name' => 'Third Secondary']);
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $fakeFile = UploadedFile::fake()->create('calculus.pdf', 1024, 'application/pdf');
        $storedPath = $fakeFile->store('memos/pdfs', 'public');

        $memo = Memo::factory()->create([
            'grade_id' => $grade->id,
            'title' => 'مذكرة التفاضل',
            'file_url' => Storage::url($storedPath),
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->get("/api/v1/student/memos/{$memo->id}/download");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_student_cannot_download_memo_from_another_grade(): void
    {
        $grade1 = Grade::factory()->create(['name' => 'Grade A']);
        $grade2 = Grade::factory()->create(['name' => 'Grade B']);

        $student = Student::factory()->create(['grade_id' => $grade1->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $memoOtherGrade = Memo::factory()->create([
            'grade_id' => $grade2->id,
            'file_url' => 'https://example.com/other.pdf',
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/memos/{$memoOtherGrade->id}/download");

        $response->assertStatus(403);
    }

    /*
    |--------------------------------------------------------------------------
    | Student Platform Feature Tests (Exams, Lessons, Profile)
    |--------------------------------------------------------------------------
    */

    public function test_student_can_list_exams_with_progress_and_unlock_status(): void
    {
        $grade = Grade::factory()->create(['name' => 'Exam Grade']);
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        $subscription = Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'Algebra Section',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'Lesson 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'lesson_id' => $lesson->id,
            'title' => 'Algebra Quiz',
            'duration_minutes' => 30,
            'passing_percentage' => 70,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson('/api/v1/student/exams');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exams retrieved successfully.',
            ])
            ->assertJsonCount(1, 'data.exams')
            ->assertJsonPath('data.exams.0.id', $exam->id);
    }

    public function test_student_can_view_single_lesson(): void
    {
        $grade = Grade::factory()->create(['name' => 'Lesson Grade']);
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        $subscription = Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'Calculus Section',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'Differentiation',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/lessons/{$lesson->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Lesson retrieved successfully.',
                'data' => [
                    'lesson' => [
                        'id' => $lesson->id,
                        'title' => 'Differentiation',
                    ],
                ],
            ]);
    }

    public function test_student_can_download_lesson_material(): void
    {
        Storage::fake('public');

        $grade = Grade::factory()->create(['name' => 'Grade 3']);
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'Calculus Section',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'Lesson With Material',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $fakePath = 'materials/handout_1.pdf';
        Storage::disk('public')->put($fakePath, '%PDF-1.4 test pdf content');

        $material = Material::create([
            'lesson_id' => $lesson->id,
            'title' => 'Handout 1',
            'file_path' => $fakePath,
            'file_type' => 'pdf',
            'file_size' => 1024,
            'is_downloadable' => true,
            'is_active' => true,
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->get("/api/v1/student/materials/{$material->id}/download");

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_student_can_view_exam_attempts_history(): void
    {
        $grade = Grade::factory()->create(['name' => 'Grade Attempt']);
        $student = Student::factory()->create(['grade_id' => $grade->id]);
        Sanctum::actingAs($student, ['*'], 'sanctum');

        $subscription = Subscription::create([
            'student_id' => $student->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        $section = Section::create([
            'grade_id' => $grade->id,
            'name' => 'Algebra Section',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $lesson = Lesson::create([
            'section_id' => $section->id,
            'title' => 'Lesson 1',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $exam = Exam::create([
            'lesson_id' => $lesson->id,
            'title' => 'Exam 1',
            'duration_minutes' => 30,
            'passing_percentage' => 60,
            'is_active' => true,
        ]);

        ExamAttempt::create([
            'student_id' => $student->id,
            'exam_id' => $exam->id,
            'subscription_id' => $subscription->id,
            'score' => 85,
            'total_score' => 100,
            'percentage' => 85,
            'is_passed' => true,
            'started_at' => now()->subHour(),
            'submitted_at' => now()->subMinutes(30),
        ]);

        $response = $this->withoutMiddleware(EnsureTrustedDevice::class)
            ->getJson("/api/v1/student/exams/{$exam->id}/attempts");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Exam attempts retrieved successfully.',
            ])
            ->assertJsonCount(1, 'data.attempts')
            ->assertJsonPath('data.attempts.0.percentage', '85.00');
    }
}
