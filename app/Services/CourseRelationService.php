<?php

namespace App\Services;

use App\Models\CourseRelation;
use App\Models\CourseSeries;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CourseRelationService
{
    public function save(User $user, CourseSeries $course, array $data, ?CourseRelation $relation = null): CourseRelation
    {
        Gate::forUser($user)->authorize('update', $course);
        abort_if($relation && $relation->course_series_id !== $course->id, 403);

        return DB::transaction(function () use ($course, $data, $relation) {
            // Serialize graph writes on one stable course row, so concurrent edges cannot create a cycle.
            CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();
            abort_unless(CourseSeries::whereKey($course->id)->exists(), 404);
            if ($relation) {
                $relation = $course->courseRelations()->whereKey($relation->id)->lockForUpdate()->firstOrFail();
            }
            $validated = Validator::make($data, [
                'related_course_series_id' => ['required', 'integer', Rule::exists('course_series', 'id')->whereNull('deleted_at'), Rule::notIn([$course->id])],
                'relation_type' => ['required', Rule::in(array_keys(CourseRelation::TYPES))],
                'sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
                'description' => ['nullable', 'string', 'max:500'],
            ], [
                'related_course_series_id.not_in' => '不能关联课程自身。',
            ])->validate();
            Validator::make($validated, [
                'related_course_series_id' => [Rule::unique('course_relations')->where('course_series_id', $course->id)
                    ->where('relation_type', $validated['relation_type'])->ignore($relation?->id)],
            ], ['related_course_series_id.unique' => '同一类型下已有关联，请编辑原记录。'])->validate();

            if ($validated['relation_type'] === 'prerequisite') {
                $this->checkPrerequisiteCycle($course->id, (int) $validated['related_course_series_id'], $relation?->id);
            }
            $relation ??= new CourseRelation;
            $relation->fill($validated);
            $relation->course_series_id = $course->id;
            $relation->save();

            return $relation;
        }, 3);
    }

    public function remove(User $user, CourseSeries $course, CourseRelation $relation): bool
    {
        Gate::forUser($user)->authorize('update', $course);
        abort_if($relation->course_series_id !== $course->id, 403);

        return DB::transaction(function () use ($course, $relation) {
            CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();
            abort_unless(CourseSeries::whereKey($course->id)->exists(), 404);

            return (bool) $course->courseRelations()->whereKey($relation->id)->firstOrFail()->delete();
        }, 3);
    }

    private function checkPrerequisiteCycle(int $source, int $target, ?int $exclude): void
    {
        $edges = CourseRelation::where('relation_type', 'prerequisite')
            ->when($exclude, fn ($query) => $query->where('id', '!=', $exclude))->get()
            ->groupBy('course_series_id');
        $pending = [$target];
        $visited = [];
        while ($pending) {
            $id = array_pop($pending);
            if ($id === $source) {
                throw ValidationException::withMessages(['related_course_series_id' => '这些前置课程形成循环，请调整关联。']);
            }
            if (isset($visited[$id])) {
                continue;
            }
            $visited[$id] = true;
            foreach ($edges->get($id, collect()) as $edge) {
                $pending[] = (int) $edge->related_course_series_id;
            }
        }
    }

    public function removeForDeletedCourse(User $user, CourseSeries $course): void
    {
        Gate::forUser($user)->authorize('forceDelete', $course);
        DB::transaction(function () use ($course) {
            CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();
            CourseRelation::where('course_series_id', $course->id)->orWhere('related_course_series_id', $course->id)->delete();
        }, 3);
    }
}
