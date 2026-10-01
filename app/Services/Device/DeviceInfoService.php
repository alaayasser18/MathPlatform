<?php

namespace App\Services\Device;

use Illuminate\Http\Request;

class DeviceInfoService
{
    public function getDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent();

        return [
            'device_name' => $this->detectDeviceName($userAgent),
            'platform' => $this->detectPlatform($userAgent),
            'browser' => $this->detectBrowser($userAgent),
            'operating_system' => $this->detectOperatingSystem($userAgent),
            'user_agent' => $userAgent,
            'ip_address' => $request->ip(),
        ];
    }

    private function detectDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        if (
            stripos($userAgent, 'iPad') !== false
            || stripos($userAgent, 'Tablet') !== false
        ) {
            return 'Tablet';
        }

        if (
            stripos($userAgent, 'Mobile') !== false
            || stripos($userAgent, 'Android') !== false
            || stripos($userAgent, 'iPhone') !== false
        ) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    private function detectPlatform(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'unknown';
        }

        if (
            stripos($userAgent, 'Android') !== false
            || stripos($userAgent, 'iPhone') !== false
            || stripos($userAgent, 'iPad') !== false
        ) {
            return 'mobile';
        }

        return 'web';
    }

    private function detectBrowser(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Browser';
        }

        if (stripos($userAgent, 'Edg/') !== false) {
            return 'Microsoft Edge';
        }

        if (stripos($userAgent, 'OPR/') !== false) {
            return 'Opera';
        }

        if (stripos($userAgent, 'Chrome/') !== false) {
            return 'Google Chrome';
        }

        if (stripos($userAgent, 'Firefox/') !== false) {
            return 'Mozilla Firefox';
        }

        if (stripos($userAgent, 'Safari/') !== false) {
            return 'Safari';
        }

        return 'Unknown Browser';
    }

    private function detectOperatingSystem(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown OS';
        }

        if (stripos($userAgent, 'Windows') !== false) {
            return 'Windows';
        }

        if (stripos($userAgent, 'Android') !== false) {
            return 'Android';
        }

        if (
            stripos($userAgent, 'iPhone') !== false
            || stripos($userAgent, 'iPad') !== false
            || stripos($userAgent, 'Mac OS') !== false
        ) {
            return 'Apple';
        }

        if (stripos($userAgent, 'Linux') !== false) {
            return 'Linux';
        }

        return 'Unknown OS';
    }
}