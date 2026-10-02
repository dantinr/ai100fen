<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('submission_key');
            $table->char('content_hash', 64);
            $table->string('title', 160);
            $table->string('category', 16);
            $table->text('goal');
            $table->text('scope')->nullable();
            $table->text('outcome');
            $table->json('completion_criteria');
            $table->timestamps();
            $table->unique(['user_id', 'submission_key']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
