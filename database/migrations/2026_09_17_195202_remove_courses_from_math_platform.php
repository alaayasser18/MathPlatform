<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove course relation from sections
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        // Remove course relation from subscription codes
        Schema::table('subscription_codes', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        // Remove course relation from subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
        });

        // Finally remove courses table
        Schema::dropIfExists('courses');
    }

    public function down(): void
    {
        // Recreate courses table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('grade_id')
                ->constrained('grades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Restore course_id in sections
        Schema::table('sections', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });

        // Restore course_id in subscription codes
        Schema::table('subscription_codes', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });

        // Restore course_id in subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('course_id')
                ->nullable()
                ->constrained('courses')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }
};