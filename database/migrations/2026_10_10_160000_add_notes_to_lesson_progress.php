<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->text('notes')->nullable();
            $table->unsignedInteger('notes_version')->default(0);
            $table->timestamp('notes_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('lesson_progress')->whereNotNull('notes')->where('notes', '!=', '')->exists()) {
            throw new RuntimeException('Classroom notes exist; back up and review their retention before removing the fields.');
        }
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropColumn(['notes', 'notes_version', 'notes_updated_at']);
        });
    }
};
