<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('live_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('slug')->unique();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('access_type', 16)->default('public');
            $table->string('meeting_provider', 80)->nullable();
            $table->text('meeting_url')->nullable();
            $table->text('replay_url')->nullable();
            $table->string('status', 16)->default('draft');
            $table->timestamps();
            $table->index(['status', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('live_sessions');
    }
};
