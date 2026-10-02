<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_series_id')->constrained('course_series')->restrictOnDelete();
            $table->foreignId('related_course_series_id')->constrained('course_series')->restrictOnDelete();
            $table->string('relation_type', 24);
            $table->unsignedInteger('sort_order')->default(1000);
            $table->string('description', 500)->nullable();
            $table->timestamps();
            $table->unique(['course_series_id', 'related_course_series_id', 'relation_type'], 'course_relations_unique');
            $table->index(['course_series_id', 'relation_type', 'sort_order'], 'course_relations_display');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_relations');
    }
};
