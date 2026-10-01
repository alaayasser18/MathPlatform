<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('lesson_id')
                ->constrained('lessons')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('title');

            // لا نخزن رابط الفيديو الحقيقي للـ student
            $table->string('storage_path');

            $table->unsignedInteger('duration_seconds')->default(0);

            // النسبة المطلوبة لاعتبار الفيديو مكتمل
            $table->unsignedTinyInteger('completion_percentage')->default(90);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};