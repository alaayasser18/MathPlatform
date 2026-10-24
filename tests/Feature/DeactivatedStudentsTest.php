<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Device;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeactivatedStudentsTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;
    private Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->grade = Grade::factory()->create();
    }

    public function test_admin_can_get_deactivated_students_list(): void
    {
        // Student 1: Active
        $activeStudent = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $activeCode = SubscriptionCode::create([
            'code' => 'ACTIVE-CODE-1',
            'is_used' => true,
            'approval_status' => 'approved',
        ]);
        Subscription::create([
            'student_id' => $activeStudent->id,
            'subscription_code_id' => $activeCode->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // Student 2: Deactivated (cancelled subscription)
        $deactivatedStudent = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $deactivatedCode = SubscriptionCode::create([
            'code' => 'CANCEL-CODE-2',
            'is_used' => true,
            'approval_status' => 'approved',
        ]);
        Subscription::create([
            'student_id' => $deactivatedStudent->id,
            'subscription_code_id' => $deactivatedCode->id,
            'status' => 'cancelled',
            'approval_status' => 'approved',
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->getJson('/api/v1/admin/deactivated-students');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals($deactivatedStudent->id, $data[0]['id']);
    }

    public function test_cannot_delete_active_student_via_deactivated_delete_endpoint(): void
    {
        $activeStudent = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $activeCode = SubscriptionCode::create([
            'code' => 'ACTIVE-CODE-3',
            'is_used' => true,
            'approval_status' => 'approved',
        ]);
        Subscription::create([
            'student_id' => $activeStudent->id,
            'subscription_code_id' => $activeCode->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/deactivated-students/{$activeStudent->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('students', ['id' => $activeStudent->id]);
    }

    public function test_admin_can_permanently_delete_deactivated_student(): void
    {
        $student = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $code = SubscriptionCode::create([
            'code' => 'CANCEL-CODE-4',
            'is_used' => true,
            'used_by_student_id' => $student->id,
            'approval_status' => 'approved',
        ]);
        $subscription = Subscription::create([
            'student_id' => $student->id,
            'subscription_code_id' => $code->id,
            'status' => 'cancelled',
            'approval_status' => 'approved',
        ]);
        $device = Device::create([
            'subscription_id' => $subscription->id,
            'device_identifier' => 'device-uuid-1234',
            'registered_at' => now(),
            'is_trusted' => true,
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/deactivated-students/{$student->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student.id', $student->id);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseMissing('subscription_codes', ['id' => $code->id]);
        $this->assertDatabaseMissing('devices', ['id' => $device->id]);
    }

    public function test_admin_can_permanently_delete_via_subscription_endpoint(): void
    {
        $student = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $code = SubscriptionCode::create([
            'code' => 'CANCEL-CODE-5',
            'is_used' => true,
            'used_by_student_id' => $student->id,
            'approval_status' => 'approved',
        ]);
        $subscription = Subscription::create([
            'student_id' => $student->id,
            'subscription_code_id' => $code->id,
            'status' => 'cancelled',
            'approval_status' => 'approved',
        ]);

        Sanctum::actingAs($this->admin, ['*'], 'sanctum');

        $response = $this->deleteJson("/api/v1/admin/subscriptions/{$subscription->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
        $this->assertDatabaseMissing('subscription_codes', ['id' => $code->id]);
    }

    public function test_student_login_with_same_code_from_different_device_returns_clear_arabic_message(): void
    {
        $student = Student::factory()->create([
            'grade_id' => $this->grade->id,
        ]);
        $code = SubscriptionCode::create([
            'code' => 'VALID-CODE-6',
            'approval_status' => 'approved',
        ]);
        $subscription = Subscription::create([
            'student_id' => $student->id,
            'subscription_code_id' => $code->id,
            'status' => 'active',
            'approval_status' => 'approved',
        ]);

        // First login from original device
        Device::create([
            'subscription_id' => $subscription->id,
            'device_identifier' => '00000000-0000-0000-0000-000000000001',
            'registered_at' => now(),
            'is_trusted' => true,
        ]);

        // Now attempt login from another device
        $response = $this->postJson('/api/v1/auth/login', [
            'subscription_code' => 'VALID-CODE-6',
            'device_identifier' => '00000000-0000-0000-0000-000000000002',
        ]);

        $response->assertStatus(422);

        $expectedMessage = 'هذا الكود مسجل بالفعل على جهاز آخر. يرجى التواصل مع الإدارة لإعادة تعيين الجهاز المصرح به.';
        $this->assertStringContainsString($expectedMessage, $response->json('message'));
        $this->assertEquals($expectedMessage, $response->json('errors.device_identifier.0'));
    }
}
