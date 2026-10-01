<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('approval_status')
                ->default('pending')
                ->after('status');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approval_status');
        });

        Schema::table('subscription_codes', function (Blueprint $table) {
            $table->string('approval_status')
                ->default('pending')
                ->after('code');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'approved_at',
            ]);
        });

        Schema::table('subscription_codes', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'approved_at',
            ]);
        });
    }
};