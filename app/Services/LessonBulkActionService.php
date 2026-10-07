<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class LessonBulkActionService
{
    public function publish(User $user, array $ids): int
    {
        return $this->apply($user, $ids, 'publish');
    }

    public function trash(User $user, array $ids): int
    {
        return $this->apply($user, $ids, 'trash');
    }

    public function restore(User $user, array $ids): int
    {
        return $this->apply($user, $ids, 'restore');
    }

    private function apply(User $user, array $ids, string $operation): int
    {
        $user = $user->fresh();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize('viewAny', Lesson::class);
        Validator::make(['ids' => $ids], [
            'ids' => ['required', 'array', 'list', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        return DB::transaction(function () use ($user, $ids, $operation): int {
            Gate::forUser($user->refresh())->authorize('viewAny', Lesson::class);
            // All lesson mutations lock parent courses first, matching progress saves.
            $courseIds = Lesson::withTrashed()->whereIn('id', $ids)->distinct()->pluck('course_series_id');
            $courses = CourseSeries::withTrashed()->whereIn('id', $courseIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $lessons = Lesson::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($lessons->count() !== count($ids)) {
                throw ValidationException::withMessages(['lessons' => '部分课时已不存在，请刷新列表后重新选择。']);
            }
            foreach ($lessons as $lesson) {
                if (! isset($courses[$lesson->course_series_id]) || $courses[$lesson->course_series_id]->trashed()) {
                    throw ValidationException::withMessages(['lessons' => '所属课程已在回收站，请先恢复课程。']);
                }
                Gate::forUser($user)->authorize('update', $courses[$lesson->course_series_id]);
                if ($lesson->trashed() !== ($operation === 'restore')) {
                    throw ValidationException::withMessages(['lessons' => '所选课时状态已变化，请刷新列表后重新选择。']);
                }
                if ($operation === 'trash' && $lesson->progress()->exists()) {
                    throw ValidationException::withMessages(['lessons' => '“'.$lesson->title.'”已有学习记录，整批未删除；请保留或归档该课时。']);
                }
                Gate::forUser($user)->authorize(match ($operation) {
                    'trash' => 'delete', 'restore' => 'restore', default => 'update',
                }, $lesson);
            }
            foreach ($lessons as $lesson) {
                try {
                    if ($operation === 'trash') {
                        $lesson->delete();
                    } elseif ($operation === 'restore') {
                        $lesson->status = 'draft';
                        $lesson->restore();
                        $lesson->series()->where('status', 'published')->update(['status' => 'draft']);
                    } else {
                        // Model validation and events apply to every record; no bulk SQL update.
                        $lesson->update(['status' => 'published']);
                    }
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages(['lessons' => '“'.$lesson->title.'”：'.implode('；', $exception->validator->errors()->all()).'。整批未改动。']);
                }
            }

            return $lessons->count();
        }, 3);
    }
}
