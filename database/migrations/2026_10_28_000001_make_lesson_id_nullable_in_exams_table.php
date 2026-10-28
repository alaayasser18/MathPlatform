<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'grade_id')) {
                $table->foreignId('grade_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('grades')
                    ->cascadeOnUpdate()
                    ->nullOnDelete();
            }

            $table->foreignId('lesson_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->foreignId('lesson_id')->nullable(false)->change();
            if (Schema::hasColumn('exams', 'grade_id')) {
                $table->dropForeign(['grade_id']);
                $table->dropColumn('grade_id');
            }
        });
    }
};
