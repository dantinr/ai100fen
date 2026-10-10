<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $guarded = ['id', 'deleted_course_snapshot', 'notes', 'notes_version', 'notes_updated_at'];

    protected function casts(): array
    {
        return ['checks' => 'array', 'completed_at' => 'datetime', 'deleted_course_snapshot' => 'array', 'notes_version' => 'integer', 'notes_updated_at' => 'datetime'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
