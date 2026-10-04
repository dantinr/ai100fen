<?php

namespace App\Services;

use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public function permanentlyDelete(User $user, CourseSeries $course, string $confirmation): void
    {
        Gate::forUser($user)->authorize('forceDelete', $course);
        DB::transaction(function () use ($user, $course, $confirmation) {
            $course = $this->lockCourse($course);
            Gate::forUser($user)->authorize('forceDelete', $course);
            if ($confirmation !== $course->slug) {
                throw ValidationException::withMessages(['confirmation' => '请输入完整课程地址标识以确认最终删除。']);
            }
            $lessons = $course->lessons()->lockForUpdate()->get();
            if (LessonProgress::whereIn('lesson_id', $lessons->modelKeys())->exists()) {
                throw ValidationException::withMessages(['confirmation' => '此课程已有学习记录，不能最终删除；请保留在回收站或恢复课程。']);
            }
            // Keep a slug-only marker so legacy arrays and install commands cannot recreate deleted content.
            DB::table('course_catalog_suppressions')->updateOrInsert(['slug' => $course->slug], [
                'created_at' => now(), 'updated_at' => now(),
            ]);
            app(CourseRelationService::class)->removeForDeletedCourse($user, $course);
            $course->lessons()->delete();
            $course->forceDelete();
        }, 3);
    }

    private function lockCourse(CourseSeries $course): CourseSeries
    {
        // Use the same graph lock as relationship writes, including courses in the recycle bin.
        CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();

        return CourseSeries::withTrashed()->whereKey($course->id)->lockForUpdate()->firstOrFail();
    }
}
