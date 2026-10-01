<?php

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Validation\ValidationException;

class StudentProfileService
{
    /**
     * Get the authenticated student's profile.
     *
     * Includes:
     * - Student personal information
     * - Student grade
     * - Current active and approved subscription
     * - Current trusted device
     */
    public function getProfile(Student $student): array
    {
        /*
        |--------------------------------------------------------------------------
        | Load Student Relationships
        |--------------------------------------------------------------------------
        */

        $student->load([
            'grade:id,name',

            'subscriptions' => function ($query) {
                $query
                    ->where('approval_status', 'approved')
                    ->where('status', 'active')
                    ->latest()
                    ->with([
                        'trustedDevice:id,subscription_id,device_identifier,device_name,platform,browser,operating_system,is_trusted,deactivated_at',
                    ]);
            },
        ]);

        /*
        |--------------------------------------------------------------------------
        | Get Current Active Subscription
        |--------------------------------------------------------------------------
        */

        $subscription = $student->subscriptions->first();

        if (!$subscription) {
            throw ValidationException::withMessages([
                'subscription' => [
                    'No active subscription was found for this student.',
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Current Trusted Device
        |--------------------------------------------------------------------------
        */

        $device = $subscription->trustedDevice;

        /*
        |--------------------------------------------------------------------------
        | Return Profile Data
        |--------------------------------------------------------------------------
        */

        return [
            'student' => $student,
            'subscription' => $subscription,
            'device' => $device,
        ];
    }
}