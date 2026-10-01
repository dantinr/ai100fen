<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_series', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category');
            $table->text('user_intent');
            $table->text('final_outcome');
            $table->json('completion_criteria');
            $table->json('agent_role');
            $table->json('human_judgment_required');
            $table->json('recommendation_keywords');
            $table->unsignedSmallInteger('minutes');
            $table->decimal('price', 10, 2)->default(100);
            $table->boolean('is_free')->default(false);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->index(['status', 'is_free']);
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_series_id')->constrained('course_series')->restrictOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->unsignedSmallInteger('score')->default(100);
            $table->unsignedSmallInteger('points')->default(100);
            $table->unsignedSmallInteger('minutes');
            $table->boolean('is_free')->default(false);
            $table->string('status')->default('draft');
            $table->text('intro');
            $table->text('goal');
            $table->json('steps');
            $table->text('prompt');
            $table->longText('code')->nullable();
            $table->string('code_filename')->nullable();
            $table->json('resources');
            $table->json('checks');
            $table->timestamps();
            $table->unique(['course_series_id', 'slug']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('lesson_id')->constrained()->restrictOnDelete();
            $table->json('checks');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('last_position_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_series');
    }
};
