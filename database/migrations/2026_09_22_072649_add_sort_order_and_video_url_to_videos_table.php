<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('video_url')
                ->after('title');

            $table->unsignedInteger('sort_order')
                ->default(0)
                ->after('video_url');

            $table->dropColumn('storage_path');
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('storage_path')
                ->after('title');

            $table->dropColumn([
                'video_url',
                'sort_order',
            ]);
        });
    }
};