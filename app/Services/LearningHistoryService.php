<?php

namespace App\Services;

use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class LearningHistoryService
{
    private function query(User $user): Builder
    {
        return LessonProgress::where('user_id', $user->id)->with([
            'lesson' => fn ($query) => $query->withTrashed()->select('id', 'course_series_id', 'slug', 'title', 'status', 'is_free', 'deleted_at'),
            'lesson.series' => fn ($query) => $query->withTrashed()->select('id', 'slug', 'title', 'status', 'is_free', 'final_outcome', 'deleted_at'),
        ]);
    }

    public function records(User $user): Collection
    {
        return $this->query($user)->orderByDesc('updated_at')->orderByDesc('id')->get()->map(fn ($record) => $this->present($record));
    }

    public function find(User $user, int $id): array
    {
        return $this->present($this->query($user)->whereKey($id)->firstOrFail());
    }

    private function present(LessonProgress $record): array
    {
        $lesson = $record->lesson;
        $course = $lesson?->series;
        $snapshot = $record->deleted_course_snapshot ?? [];
        $available = $lesson && $course && app(CourseAccessService::class)->canAccess($course, $lesson);
        $removed = $snapshot !== [] || ! $lesson || ! $course || $lesson->trashed() || $course->trashed()
            || $lesson->status === 'archived' || $course->status === 'archived';

        return [
            'id' => $record->id, 'course_id' => $course?->id ?? $snapshot['course_id'] ?? 'record-'.$record->id,
            'course_title' => $course?->title ?? $snapshot['course_title'] ?? '已下架课程',
            'lesson_title' => $lesson?->title ?? $snapshot['lesson_title'] ?? '历史课时',
            'course' => $course, 'lesson' => $lesson,
            'progress_percent' => $record->progress_percent, 'completed_at' => $record->completed_at,
            'notes' => $record->notes,
            'available' => (bool) $available, 'removed' => $removed,
            'notice' => $removed ? '课程已下架' : '课程暂未开放',
            'url' => route('learning.history', $record->id),
        ];
    }

    public function courseProgress(User $user, Collection $records): Collection
    {
        return $records->groupBy('course_id')->map(function (Collection $items) use ($user) {
            $entry = $items->firstWhere('available', true) ?? $items->first();
            $entry['percent'] = $entry['available'] ? app(ProgressService::class)->seriesPercent($user, $entry['course']) : null;

            return $entry;
        });
    }
}
