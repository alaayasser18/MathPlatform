<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained('students')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('exam_id')
                ->constrained('exams')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->constrained('subscriptions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('score')->default(0);

            $table->unsignedInteger('total_score')->default(0);

            $table->decimal('percentage', 5, 2)->default(0);

            $table->boolean('is_passed')->default(false);

            $table->timestamp('started_at')->nullable();

            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};