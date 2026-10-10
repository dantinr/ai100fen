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
    public function snapshot(CourseSeries $course, string $type): string
    {
        return hash('sha256', $course->courseRelations()->where('relation_type', $type)->reorder('id')
            ->get(['id', 'related_course_series_id', 'sort_order', 'description'])->toJson());
    }

    public function sync(User $user, CourseSeries $course, string $type, array $rows, string $snapshot): void
    {
        $user = $user->fresh();
        abort_unless($user, 403);
        Gate::forUser($user)->authorize('update', $course);
        abort_unless(in_array($type, ['prerequisite', 'next'], true), 422);

        DB::transaction(function () use ($user, $course, $type, $rows, $snapshot) {
            CourseSeries::withTrashed()->orderBy('id')->lockForUpdate()->firstOrFail();
            abort_unless(CourseSeries::whereKey($course->id)->exists(), 404);
            if ($snapshot !== $this->snapshot($course, $type)) {
                throw ValidationException::withMessages(['' => '课程关联已被更新，请刷新页面后再保存。']);
            }
            $rows = Validator::make(['rows' => $rows], [
                'rows' => ['array'],
                'rows.*' => ['array:id,related_course_series_id,sort_order,description'],
                'rows.*.id' => ['nullable', 'integer'],
                'rows.*.related_course_series_id' => ['required', 'integer', 'distinct'],
                'rows.*.sort_order' => ['required', 'integer', 'min:0', 'max:999999'],
                'rows.*.description' => ['nullable', 'string', 'max:500'],
            ])->validate()['rows'];
            $existing = $course->courseRelations()->where('relation_type', $type)->lockForUpdate()->get()->keyBy('id');
            $kept = [];
            foreach ($rows as $index => $row) {
                if (filled($row['id'] ?? null)) {
                    if (! $existing->has($row['id']) || in_array((int) $row['id'], $kept, true)) {
                        throw ValidationException::withMessages(['rows.'.$index.'.related_course_series_id' => '关联已失效或不属于当前课程。']);
                    }
                    $kept[] = (int) $row['id'];
                }
            }
            foreach ($existing as $relation) {
                if (! in_array($relation->id, $kept, true)) {
                    $this->remove($user, $course, $relation);
                }
            }
            foreach ($rows as $index => $row) {
                $relation = $existing->get($row['id'] ?? null);
                if ($relation && (int) $row['related_course_series_id'] === $relation->related_course_series_id
                    && (int) $row['sort_order'] === $relation->sort_order
                    && ($row['description'] ?? null) === $relation->description) {
                    continue;
                }
                try {
                    $this->save($user, $course, [
                        'related_course_series_id' => $row['related_course_series_id'],
                        'relation_type' => $type, 'sort_order' => $row['sort_order'],
                        'description' => $row['description'] ?? null,
                    ], $relation);
                } catch (ValidationException $exception) {
                    $errors = [];
                    foreach ($exception->errors() as $field => $messages) {
                        $errors['rows.'.$index.'.'.$field] = $messages;
                    }
                    throw ValidationException::withMessages($errors);
                }
            }
        }, 3);
    }

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
