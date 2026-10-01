<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Lesson extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_free' => 'boolean', 'steps' => 'array', 'checks' => 'array', 'resources' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $lesson) {
            if ($lesson->status === 'published' && (blank($lesson->goal) || blank($lesson->prompt) || empty($lesson->steps) || empty($lesson->checks))) {
                throw ValidationException::withMessages(['lesson' => '发布前需补齐目标、步骤、Prompt 与验收标准。']);
            }
        });
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(CourseSeries::class, 'course_series_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
