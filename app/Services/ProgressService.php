<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgressService
{
    public function completedLessonIds(User $user, CourseSeries $series): array
    {
        return LessonProgress::where('user_id', $user->id)
            ->whereIn('lesson_id', $series->lessons()->where('status', 'published')->select('id'))
            ->whereNotNull('completed_at')->pluck('lesson_id')->all();
    }

    public function seriesPercent(User $user, CourseSeries $series): int|float
    {
        $lessons = $series->lessons()->where('status', '!=', 'archived')->get(['id', 'status']);
        $total = $lessons->count();
        if ($total === 0) {
            return 0;
        }
        $completed = LessonProgress::where('user_id', $user->id)->whereIn('lesson_id', $lessons->modelKeys())
            ->whereNotNull('completed_at')->pluck('lesson_id');

        $count = $lessons->where('status', 'published')->whereIn('id', $completed)->count();
        // Only every current step being accepted can display 100%.
        $percent = $count === $total ? 100 : min(99.9, round($count * 100 / $total, 1));

        return floor($percent) === (float) $percent ? (int) $percent : $percent;
    }

    public function save(User $user, Lesson $lesson, array $checks): LessonProgress
    {
        abort_unless(app(CourseAccessService::class)->canAccess($lesson->series, $lesson), 403);

        return DB::transaction(function () use ($user, $lesson, $checks) {
            // Serialize updates for this user, including simultaneous first saves.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $series = CourseSeries::whereKey($lesson->course_series_id)->lockForUpdate()->first();
            abort_unless($series, 403);
            $current = Lesson::whereKey($lesson->id)->lockForUpdate()->firstOrFail();
            abort_unless(app(CourseAccessService::class)->canAccess($series, $current), 403);
            if ($current->checks !== $lesson->checks || empty($current->checks)
                || ! array_is_list($checks) || count($checks) !== count($current->checks)
                || collect($checks)->contains(fn ($check) => ! is_bool($check))) {
                throw ValidationException::withMessages(['checks' => '验收清单已变化或不完整，请刷新后重试。']);
            }
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
