<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\Lesson;

class CourseAccessService
{
    public function canAccess(CourseSeries $series, Lesson $lesson): bool
    {
        // Purchases and subscriptions are not implemented; never infer them from input.
        return $lesson->course_series_id === $series->id
            && ! $lesson->trashed()
            && ! $series->trashed()
            && Lesson::whereKey($lesson->id)->where('course_series_id', $series->id)->where('status', 'published')
                ->whereHas('series', fn ($query) => $query->where('status', 'published'))
                ->where(fn ($query) => $query->where('is_free', true)
                    ->orWhereHas('series', fn ($course) => $course->where('is_free', true)))
                ->exists();
    }

    public function canPreviewLegacy(array $series, array $lesson): bool
    {
        return $series['available'] && $lesson['is_free'];
    }
}
