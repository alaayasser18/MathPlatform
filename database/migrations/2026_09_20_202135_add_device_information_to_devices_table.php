<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('browser')
                ->nullable()
                ->after('platform');

            $table->string('operating_system')
                ->nullable()
                ->after('browser');

            $table->text('user_agent')
                ->nullable()
                ->after('operating_system');

            $table->string('ip_address', 45)
                ->nullable()
                ->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'browser',
                'operating_system',
                'user_agent',
                'ip_address',
            ]);
        });
    }
};