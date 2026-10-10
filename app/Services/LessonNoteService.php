<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LessonNoteService
{
    public function save(User $user, Lesson $lesson, string $notes, int $version): LessonProgress
    {
        return DB::transaction(function () use ($user, $lesson, $notes, $version) {
            // Match progress saves: user, course, then lesson; concurrent first saves stay unique.
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $series = CourseSeries::whereKey($lesson->course_series_id)->lockForUpdate()->first();
            $current = Lesson::whereKey($lesson->id)->lockForUpdate()->first();
            abort_unless($series && $current && app(CourseAccessService::class)->canAccess($series, $current), 403);
            $record = LessonProgress::firstOrNew(['user_id' => $user->id, 'lesson_id' => $current->id]);
            $currentVersion = (int) ($record->notes_version ?? 0);
            // An identical retry is safe, including a successful save whose response was lost.
            if ($currentVersion !== $version) {
                abort_unless($record->exists && ($record->notes ?? '') === $notes, 409, '笔记已在其他页面更新。当前文字已保留，请复制备份后刷新再编辑。');

                return $record;
            }
            if ($record->exists && ($record->notes ?? '') === $notes) {
                return $record;
            }
            if (! $record->exists) {
                $record->fill(['checks' => [], 'progress_percent' => 0, 'completed_at' => null]);
            }
            $record->forceFill([
                'notes' => $notes === '' ? null : $notes,
                'notes_version' => $currentVersion + 1,
                'notes_updated_at' => now(),
            ])->save();

            return $record;
        });
    }
}
