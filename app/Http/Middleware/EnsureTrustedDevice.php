<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrustedDevice
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Make sure the request is authenticated
        |--------------------------------------------------------------------------
        */

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Get the current Sanctum access token
        |--------------------------------------------------------------------------
        */

        $accessToken = $user->currentAccessToken();

        if (!$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication token.',
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | Get the device linked to this token
        |--------------------------------------------------------------------------
        */

        if (!$accessToken->device_id) {
            return response()->json([
                'success' => false,
                'message' => 'This session is not linked to a device.',
            ], 403);
        }

        $device = Device::query()
            ->with('subscription')
            ->find($accessToken->device_id);

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'The device linked to this session no longer exists.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify device belongs to the authenticated student's subscription
        |--------------------------------------------------------------------------
        */

        if (
            !$device->subscription ||
            $device->subscription->student_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This device is not linked to your subscription.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Verify trusted device status
        |--------------------------------------------------------------------------
        */

        if (
            !$device->is_trusted ||
            $device->deactivated_at !== null
        ) {
            return response()->json([
                'success' => false,
                'message' => 'This device is no longer trusted.',
            ], 403);
        }

        /*
        |--------------------------------------------------------------------------
        | Update last activity
        |--------------------------------------------------------------------------
        */

        $device->update([
            'last_seen_at' => now(),
        ]);

        return $next($request);
    }
}