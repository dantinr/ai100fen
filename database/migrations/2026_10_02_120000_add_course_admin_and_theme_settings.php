<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->boolean('is_admin')->default(false));
        Schema::table('course_series', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->json('objectives')->nullable();
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->json('objectives')->nullable();
            $table->longText('content')->nullable();
            $table->text('video_url')->nullable();
        });
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
        Schema::table('lessons', fn (Blueprint $table) => $table->dropColumn(['objectives', 'content', 'video_url']));
        Schema::table('course_series', fn (Blueprint $table) => $table->dropColumn(['description', 'objectives']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_admin'));
    }
};
