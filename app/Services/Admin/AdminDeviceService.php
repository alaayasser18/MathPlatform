<?php

namespace App\Services\Admin;

use App\Models\Device;
use App\Models\Subscription;
use Illuminate\Validation\ValidationException;

class AdminDeviceService
{
    public function resetTrustedDevice(
        Subscription $subscription
    ): Device {
        $device = Device::query()
            ->where('subscription_id', $subscription->id)
            ->where('is_trusted', true)
            ->whereNull('deactivated_at')
            ->first();

        if (!$device) {
            throw ValidationException::withMessages([
                'device' => [
                    'This subscription does not have an active trusted device.'
                ],
            ]);
        }

        $device->update([
            'is_trusted' => false,
            'deactivated_at' => now(),
        ]);

        return $device->fresh();
    }
}