<?php

namespace App\Services\Auth;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function login(string $email, string $password): array
    {
        $admin = Admin::where('email', $email)->first();

        if (!$admin) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$admin->is_active) {
            throw ValidationException::withMessages([
                'email' => ['This admin account is inactive.'],
            ]);
        }

        if (!Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $admin->createToken(
            'admin-token',
            ['admin']
        )->plainTextToken;

        return [
            'admin' => $admin,
            'token' => $token,
        ];
    }
}