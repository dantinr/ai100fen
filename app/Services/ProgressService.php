<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public function seriesScore(User $user, CourseSeries $series): int
    {
        $lessons = $series->lessons()->where('status', '!=', 'archived')->get();
        $total = $lessons->sum('points');
        if ($total === 0) {
            return 0;
        }
        $completed = LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $lessons->modelKeys())
            ->whereNotNull('completed_at')->pluck('lesson_id');

        return (int) floor($lessons->where('status', 'published')->whereIn('id', $completed)->sum('points') * 100 / $total);
    }

    public function save(User $user, Lesson $lesson, array $checks): LessonProgress
    {
        abort_unless(app(CourseAccessService::class)->canAccess($lesson->series, $lesson), 403);

        return DB::transaction(function () use ($user, $lesson, $checks) {
            // Serialize updates for this user, including simultaneous first saves.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $series = CourseSeries::whereKey($lesson->course_series_id)->lockForUpdate()->first();
            abort_unless($series && app(CourseAccessService::class)->canAccess($series, $lesson), 403);
            $progress = LessonProgress::firstOrNew(['user_id' => $user->id, 'lesson_id' => $lesson->id]);
            $percent = (int) floor(count(array_filter($checks)) * 100 / count($lesson->checks));
            $progress->fill([
                'checks' => $checks,
                'progress_percent' => $percent,
                'completed_at' => $percent === 100 ? ($progress->completed_at ?? now()) : null,
            ])->save();

            return $progress;
        });
    }
}
