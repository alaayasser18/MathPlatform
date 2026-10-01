<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('device_identifier');

            $table->string('device_name')->nullable();

            $table->string('platform')->nullable();

            $table->timestamp('registered_at');

            $table->timestamp('last_seen_at')->nullable();

            $table->boolean('is_trusted')->default(true);

            $table->timestamp('deactivated_at')->nullable();

            $table->timestamps();

            $table->unique(['subscription_id', 'device_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};