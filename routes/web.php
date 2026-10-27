<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/sync-initial-data/{token}', function (string $token) {
    if ($token !== 'math2026sync') {
        abort(403, 'Unauthorized access.');
    }

    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = \Illuminate\Support\Facades\Artisan::output();

        \Illuminate\Support\Facades\Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\ProductionSyncSeeder',
            '--force' => true,
        ]);
        $seedOutput = \Illuminate\Support\Facades\Artisan::output();

        return response()->json([
            'success' => true,
            'message' => 'Database migrated and data synced successfully!',
            'migrate_output' => $migrateOutput,
            'seed_output' => $seedOutput,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
        ], 500);
    }
});

