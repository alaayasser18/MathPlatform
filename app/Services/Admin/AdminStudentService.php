<?php

namespace App\Services\Admin;

use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminStudentService
{
    public function getAllStudents()
    {
        return Student::query()
            ->with([
                'grade:id,name',
                'subscriptions' => function ($query) {
                    $query
                        ->latest()
                        ->with([
                            'subscriptionCode:id,code,expires_at,approval_status'
                        ]);
                },
            ])
            ->latest()
            ->paginate(15);
    }

    public function getStudent(Student $student): Student
    {
        return $student->load([
            'grade:id,name',
            'enrollments',
            'subscriptions' => function ($query) {
                $query
                    ->latest()
                    ->with([
                        'subscriptionCode'
                    ]);
            },
        ]);
    }

    public function createStudent(array $data): Student
    {
        return DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | 1. Create Student
            |--------------------------------------------------------------------------
            */

            $student = Student::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'parent_phone' => $data['parent_phone'],
                'grade_id' => $data['grade_id'],
                'registered_at' => today(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 2. Create Enrollment
            |--------------------------------------------------------------------------
            */

            $academicYear = $this->getAcademicYear();

            Enrollment::create([
                'student_id' => $student->id,
                'grade_id' => $data['grade_id'],
                'academic_year' => $academicYear,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 3. Generate Subscription Code
            |--------------------------------------------------------------------------
            */

            $code = $this->generateUniqueCode();

            $subscriptionCode = SubscriptionCode::create([
                'code' => $code,
                'expires_at' => $data['code_expires_at'] ?? null,
                'is_used' => false,
                'used_by_student_id' => null,
                'used_at' => null,
                'approval_status' => 'approved',
                'approved_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | 4. Create Active Subscription
            |--------------------------------------------------------------------------
            */

            Subscription::create([
                'student_id' => $student->id,
                'subscription_code_id' => $subscriptionCode->id,
                'status' => 'active',
                'approval_status' => 'approved',
                'approved_at' => now(),
                'start_date' => $data['start_date'] ?? today(),
                'end_date' => $data['end_date'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 5. Return Student With Relations
            |--------------------------------------------------------------------------
            */

            return $student->fresh([
                'grade',
                'enrollments',
                'subscriptions.subscriptionCode',
            ]);
        });
    }

    public function updateStudent(
        Student $student,
        array $data
    ): Student {
        $student->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'],
            'parent_phone' => $data['parent_phone'],
            'grade_id' => $data['grade_id'],
        ]);

        return $student->fresh([
            'grade',
            'enrollments',
            'subscriptions.subscriptionCode',
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(
                Str::random(10)
            );
        } while (
            SubscriptionCode::where(
                'code',
                $code
            )->exists()
        );

        return $code;
    }

    private function getAcademicYear(): string
    {
        $year = now()->year;

        return $year . '/' . ($year + 1);
    }
}