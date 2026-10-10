<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CourseDeletionService
{
    public function trash(User $user, CourseSeries $course): void
    {
        Gate::forUser($user)->authorize('delete', $course);
        DB::transaction(function () use ($user, $course) {
            $course = $this->lockCourse($course);
            Gate::forUser($user)->authorize('delete', $course);
            $course->delete();
        }, 3);
    }

    public function restore(User $user, CourseSeries $course): void
    {
        Gate::forUser($user)->authorize('restore', $course);
        DB::transaction(function () use ($user, $course) {
            $course = $this->lockCourse($course);
            Gate::forUser($user)->authorize('restore', $course);
            // Restoring content does not automatically make it publicly accessible.
            $course->status = 'draft';
            $course->restore();
        }, 3);
    }

    public function permanentlyDelete(User $user, CourseSeries $course): void
    {
        $user = $user->fresh();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize('forceDelete', $course);
        DB::transaction(function () use ($user, $course) {
            $course = $this->lockCourse($course);
            Gate::forUser($user->refresh())->authorize('forceDelete', $course);
            $lessons = $course->lessons()->withTrashed()->lockForUpdate()->get();
            if (LessonProgress::whereIn('lesson_id', $lessons->modelKeys())->exists()) {
                throw ValidationException::withMessages(['confirmation' => '此课程已有学习记录，不能最终删除；请保留在回收站或恢复课程。']);
            }
            // Keep a slug-only marker so legacy arrays and install commands cannot recreate deleted content.
            DB::table('course_catalog_suppressions')->updateOrInsert(['slug' => $course->slug], [
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(CourseRelationService::class)->removeForDeletedCourse($user, $course);
            $course->lessons()->withTrashed()->forceDelete();
            $course->forceDelete();
        }, 3);
    }

    public function permanentlyDeleteMany(User $user, array $ids): int
    {
        $user = $user->fresh();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize('viewAny', CourseSeries::class);
        Validator::make(['ids' => $ids], [
            'ids' => ['required', 'array', 'list', 'min:1'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
        ])->validate();

        return DB::transaction(function () use ($user, $ids): int {
            Gate::forUser($user->refresh())->authorize('viewAny', CourseSeries::class);
            // Acquire the shared relationship lock before all selected course locks.
            CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->first();
            $courses = CourseSeries::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            if ($courses->count() !== count($ids)) {
                throw ValidationException::withMessages(['confirmation' => '部分课程已不存在，请刷新回收站后重新选择；整批未删除。']);
            }
            foreach ($courses as $course) {
                if (! $course->trashed()) {
                    throw ValidationException::withMessages(['confirmation' => '“'.$course->title.'”已不在回收站；整批未删除。']);
                }
                Gate::forUser($user)->authorize('forceDelete', $course);
            }
            foreach ($courses as $course) {
                // Reuse all single-course protections and cleanup in one outer transaction.
                $this->permanentlyDelete($user, $course);
            }

            return $courses->count();
        }, 3);
    }

    private function lockCourse(CourseSeries $course): CourseSeries
    {
        // Use the same graph lock as relationship writes, including courses in the recycle bin.
        CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();

        return CourseSeries::withTrashed()->whereKey($course->id)->lockForUpdate()->firstOrFail();
    }
}
