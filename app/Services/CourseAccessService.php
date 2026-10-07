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
            && Lesson::whereKey($lesson->id)->where('status', 'published')->exists()
            && ! $series->trashed()
            && CourseSeries::whereKey($series->id)->where('status', 'published')->exists()
            && $series->status === 'published' && $lesson->status === 'published'
            && ($series->is_free || $lesson->is_free);
    }

    public function canPreviewLegacy(array $series, array $lesson): bool
    {
        return $series['available'] && $lesson['is_free'];
    }
}
