<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('code')->unique();

            $table->date('expires_at')->nullable();

            $table->boolean('is_used')->default(false);

            $table->foreignId('used_by_student_id')
                ->nullable()
                ->constrained('students')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->timestamp('used_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_codes');
    }
};