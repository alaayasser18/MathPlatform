<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->enum('status', [
                'locked',
                'not_started',
                'in_progress',
                'completed',
            ])->default('locked');

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique([
                'subscription_id',
                'lesson_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};