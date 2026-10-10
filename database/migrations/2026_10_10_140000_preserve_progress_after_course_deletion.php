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
            $table->json('deleted_course_snapshot')->nullable();
            $table->dropForeign(['lesson_id']);
        });
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('lesson_id')->nullable()->change();
            // Only the deletion service detaches records after taking a snapshot.
            $table->foreign('lesson_id')->references('id')->on('lessons')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::table('lesson_progress')->whereNull('lesson_id')->exists()) {
            throw new RuntimeException('Archived learning records exist; restoring a non-null lesson_id would lose history.');
        }
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropForeign(['lesson_id']);
        });
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->unsignedBigInteger('lesson_id')->nullable(false)->change();
            $table->foreign('lesson_id')->references('id')->on('lessons')->restrictOnDelete();
            $table->dropColumn('deleted_course_snapshot');
        });
    }
};
