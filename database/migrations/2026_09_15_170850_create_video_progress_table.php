<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('video_id')
                ->constrained('videos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('current_position_seconds')->default(0);

            $table->unsignedInteger('watched_duration_seconds')->default(0);

            $table->unsignedTinyInteger('completion_percentage')->default(0);

            $table->boolean('is_completed')->default(false);

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'subscription_id',
                'video_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_progress');
    }
};