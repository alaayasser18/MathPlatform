<?php

namespace App\Services\Admin;

use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminActivationService
{
    public function getPendingRequests()
    {
        return Subscription::query()
            ->with([
                'student:id,first_name,last_name,phone,parent_phone,grade_id',
                'student.grade:id,name',
                'subscriptionCode:id,approval_status,is_used',
            ])
            ->where('approval_status', 'pending')
            ->latest()
            ->paginate(15);
    }

    public function approve(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(function () use ($subscription) {

            if ($subscription->approval_status !== 'pending') {
                throw ValidationException::withMessages([
                    'subscription' => [
                        'This activation request has already been processed.'
                    ],
                ]);
            }

            if (!$subscription->subscriptionCode) {
                throw ValidationException::withMessages([
                    'subscription' => [
                        'This subscription does not have a subscription code.'
                    ],
                ]);
            }

            $now = now();

            $subscription->update([
                'status' => 'active',
                'approval_status' => 'approved',
                'approved_at' => $now,
                'start_date' => today(),
            ]);

            $subscription->subscriptionCode->update([
                'approval_status' => 'approved',
                'approved_at' => $now,
            ]);

            return $subscription->fresh([
                'student',
                'student.grade',
                'subscriptionCode',
            ]);
        });
    }

    public function cancel(
        Subscription $subscription
    ): Subscription {
        return DB::transaction(function () use ($subscription) {

            if ($subscription->approval_status !== 'pending') {
                throw ValidationException::withMessages([
                    'subscription' => [
                        'This activation request has already been processed.'
                    ],
                ]);
            }

            if (!$subscription->subscriptionCode) {
                throw ValidationException::withMessages([
                    'subscription' => [
                        'This subscription does not have a subscription code.'
                    ],
                ]);
            }

            $subscription->update([
                'status' => 'cancelled',
                'approval_status' => 'rejected',
            ]);

            $subscription->subscriptionCode->update([
                'approval_status' => 'rejected',
            ]);

            return $subscription->fresh([
                'student',
                'student.grade',
                'subscriptionCode',
            ]);
        });
    }

    public function getApprovedStudents()
    {
        return Student::query()
            ->with([
                'grade:id,name',
                'subscriptions' => function ($query) {
                    $query
                        ->where('approval_status', 'approved')
                        ->with([
                            'subscriptionCode:id,code,expires_at,approval_status'
                        ]);
                },
            ])
            ->whereHas('subscriptions', function ($query) {
                $query->where('approval_status', 'approved');
            })
            ->latest()
            ->paginate(15);
    }

    public function deactivateSubscription(
        Subscription $subscription
    ): Subscription {
        if ($subscription->status === 'cancelled') {
            throw ValidationException::withMessages([
                'subscription' => [
                    'This subscription is already deactivated.'
                ],
            ]);
        }

        if ($subscription->status === 'expired') {
            throw ValidationException::withMessages([
                'subscription' => [
                    'This subscription has already expired.'
                ],
            ]);
        }

        if ($subscription->status !== 'active') {
            throw ValidationException::withMessages([
                'subscription' => [
                    'Only active subscriptions can be deactivated.'
                ],
            ]);
        }

        $subscription->update([
            'status' => 'cancelled',
        ]);

        return $subscription->fresh([
            'student',
            'subscriptionCode',
        ]);
    }
}