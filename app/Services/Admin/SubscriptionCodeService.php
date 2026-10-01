<?php

namespace App\Services\Admin;

use App\Models\Grade;
use App\Models\Student;

class SubscriptionCodeService
{
    public function getCodesByGrade(int $gradeId): array
    {
        $grade = Grade::query()
            ->select([
                'id',
                'name',
            ])
            ->findOrFail($gradeId);

        $students = Student::query()
            ->where('grade_id', $gradeId)
            ->with([
                'subscriptions' => function ($query) {
                    $query
                        ->where('approval_status', 'approved')
                        ->where('status', 'active')
                        ->with([
                            'subscriptionCode:id,code,expires_at,approval_status',
                        ]);
                },
            ])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return [
            'grade' => $grade,
            'students' => $students,
        ];
    }
}