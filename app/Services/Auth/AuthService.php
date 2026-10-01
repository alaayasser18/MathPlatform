<?php

namespace App\Services\Auth;

use App\Models\Device;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\Subscription;
use App\Models\SubscriptionCode;
use App\Services\Device\DeviceInfoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private DeviceInfoService $deviceInfoService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Activate Student Account
    |--------------------------------------------------------------------------
    */

    public function activate(array $data): void
    {
        DB::transaction(function () use ($data) {

            /*
            |--------------------------------------------------------------------------
            | Create Student
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
            | Create Enrollment
            |--------------------------------------------------------------------------
            */

            Enrollment::create([
                'student_id' => $student->id,
                'grade_id' => $data['grade_id'],
                'academic_year' => now()->year,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Subscription
            |--------------------------------------------------------------------------
            */

            $subscription = Subscription::create([
                'student_id' => $student->id,
                'start_date' => null,
                'end_date' => null,
                'status' => 'pending',
                'approval_status' => 'pending',
                'approved_at' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Generate Subscription Code
            |--------------------------------------------------------------------------
            */

            $code = $this->generateUniqueSubscriptionCode();

            $subscriptionCode = SubscriptionCode::create([
                'code' => $code,
                'expires_at' => null,
                'is_used' => false,
                'used_by_student_id' => null,
                'used_at' => null,
                'approval_status' => 'pending',
                'approved_at' => null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Link Subscription With Code
            |--------------------------------------------------------------------------
            */

            $subscription->update([
                'subscription_code_id' => $subscriptionCode->id,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Student Login
    |--------------------------------------------------------------------------
    */

    public function login(
        string $subscriptionCode,
        string $deviceIdentifier,
        Request $request
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Find Subscription Code
        |--------------------------------------------------------------------------
        */

        $code = SubscriptionCode::query()
            ->where('code', $subscriptionCode)
            ->first();

        if (!$code) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'Invalid subscription code.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Code Approval
        |--------------------------------------------------------------------------
        */

        if ($code->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription code has not been approved yet.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Code Expiration
        |--------------------------------------------------------------------------
        */

        if (
            $code->expires_at !== null &&
            $code->expires_at->isPast()
        ) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription code has expired.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Find Subscription
        |--------------------------------------------------------------------------
        */

        $subscription = Subscription::query()
            ->with([
                'student',
                'subscriptionCode',
            ])
            ->where('subscription_code_id', $code->id)
            ->first();

        if (!$subscription) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription is not linked to a valid subscription.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Subscription Approval
        |--------------------------------------------------------------------------
        */

        if ($subscription->approval_status !== 'approved') {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription has not been approved yet.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Subscription Status
        |--------------------------------------------------------------------------
        */

        if ($subscription->status !== 'active') {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription is not active.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check Subscription Dates
        |--------------------------------------------------------------------------
        */

        if (
            $subscription->start_date !== null &&
            $subscription->start_date->isFuture()
        ) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription has not started yet.'
                ],
            ]);
        }

        if (
            $subscription->end_date !== null &&
            $subscription->end_date->isPast()
        ) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'This subscription has expired.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Student
        |--------------------------------------------------------------------------
        */

        $student = $subscription->student;

        if (!$student) {
            throw ValidationException::withMessages([
                'subscription_code' => [
                    'No student is linked to this subscription.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Get Device Information
        |--------------------------------------------------------------------------
        */

        $deviceInfo = $this->deviceInfoService
            ->getDeviceInfo($request);

        /*
        |--------------------------------------------------------------------------
        | Find Current Trusted Device
        |--------------------------------------------------------------------------
        */

        $trustedDevice = Device::query()
            ->where('subscription_id', $subscription->id)
            ->where('is_trusted', true)
            ->whereNull('deactivated_at')
            ->first();

        /*
        |--------------------------------------------------------------------------
        | Existing Trusted Device
        |--------------------------------------------------------------------------
        */

        if ($trustedDevice) {

            /*
            |--------------------------------------------------------------------------
            | Different Device
            |--------------------------------------------------------------------------
            */

            if (
                $trustedDevice->device_identifier !==
                $deviceIdentifier
            ) {
                throw ValidationException::withMessages([
                    'device_identifier' => [
                        'Another device is already registered for this subscription. Please contact the administrator to reset the trusted device.'
                    ],
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Same Trusted Device
            |--------------------------------------------------------------------------
            */

            $device = $trustedDevice;

            $device->update([
                'device_name' => $deviceInfo['device_name'],
                'platform' => $deviceInfo['platform'],
                'browser' => $deviceInfo['browser'],
                'operating_system' => $deviceInfo['operating_system'],
                'user_agent' => $deviceInfo['user_agent'],
                'ip_address' => $deviceInfo['ip_address'],
                'last_seen_at' => now(),
            ]);
        } else {

            /*
            |--------------------------------------------------------------------------
            | No Active Trusted Device
            |--------------------------------------------------------------------------
            */

            $device = Device::query()
                ->where('subscription_id', $subscription->id)
                ->where(
                    'device_identifier',
                    $deviceIdentifier
                )
                ->first();

            /*
            |--------------------------------------------------------------------------
            | Existing Deactivated Device
            |--------------------------------------------------------------------------
            */

            if ($device) {

                $device->update([
                    'device_name' => $deviceInfo['device_name'],
                    'platform' => $deviceInfo['platform'],
                    'browser' => $deviceInfo['browser'],
                    'operating_system' => $deviceInfo['operating_system'],
                    'user_agent' => $deviceInfo['user_agent'],
                    'ip_address' => $deviceInfo['ip_address'],
                    'is_trusted' => true,
                    'deactivated_at' => null,
                    'last_seen_at' => now(),
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Create New Device
            |--------------------------------------------------------------------------
            */

            else {

                $device = Device::create([
                    'subscription_id' => $subscription->id,
                    'device_identifier' => $deviceIdentifier,
                    'device_name' => $deviceInfo['device_name'],
                    'platform' => $deviceInfo['platform'],
                    'browser' => $deviceInfo['browser'],
                    'operating_system' => $deviceInfo['operating_system'],
                    'user_agent' => $deviceInfo['user_agent'],
                    'ip_address' => $deviceInfo['ip_address'],
                    'registered_at' => now(),
                    'last_seen_at' => now(),
                    'is_trusted' => true,
                    'deactivated_at' => null,
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Create Sanctum Token
        |--------------------------------------------------------------------------
        */

        $tokenResult = $student->createToken(
            'student-token',
            ['student']
        );

        /*
        |--------------------------------------------------------------------------
        | Bind Token To Device
        |--------------------------------------------------------------------------
        */

        $tokenResult->accessToken->device_id = $device->id;
        $tokenResult->accessToken->save();

        /*
        |--------------------------------------------------------------------------
        | Get Plain Text Token
        |--------------------------------------------------------------------------
        */

        $token = $tokenResult->plainTextToken;

        /*
        |--------------------------------------------------------------------------
        | Return Login Result
        |--------------------------------------------------------------------------
        */

        return [
            'student' => $student,
            'subscription' => $subscription,
            'device' => $device->fresh(),
            'token' => $token,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Generate Unique Subscription Code
    |--------------------------------------------------------------------------
    */

    private function generateUniqueSubscriptionCode(): string
    {
        do {
            $code = strtoupper(
                substr(
                    str_shuffle(
                        'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
                    ),
                    0,
                    10
                )
            );
        } while (
            SubscriptionCode::query()
                ->where('code', $code)
                ->exists()
        );

        return $code;
    }
}