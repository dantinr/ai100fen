<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_series', function (Blueprint $table) {
            $table->softDeletes()->index();
        });
        // Legacy preview content must not reappear after its database course is permanently deleted.
        Schema::create('course_catalog_suppressions', function (Blueprint $table) {
            $table->string('slug', 150)->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_catalog_suppressions');
        Schema::table('course_series', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
