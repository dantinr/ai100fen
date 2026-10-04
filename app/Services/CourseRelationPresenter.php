<?php

namespace App\Services;

use App\Models\CourseRelation;
use App\Models\CourseSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CourseRelationPresenter
{
    public function publicCourses(): Builder
    {
        return CourseSeries::where('status', 'published')->whereHas('lessons', fn ($query) => $query->where('status', 'published'))
            ->where(fn ($query) => $query->where('is_free', false)->orWhere(fn ($query) => $query->freeLab()));
    }

    public function forLegacy(string $slug): array
    {
        if (! Schema::hasTable('course_relations')) {
            return [];
        }
        $course = $this->publicCourses()->where('slug', $slug)->first();

        return $course ? $this->groups($course) : [];
    }

    public function groups(CourseSeries $course, bool $preview = false): array
    {
        if (! Schema::hasTable('course_relations') || (! $preview && ! $this->publicCourses()->whereKey($course->id)->exists())) {
            return [];
        }
        $query = $course->courseRelations()->with(['relatedCourse' => fn ($query) => $query
            ->select(['id', 'slug', 'title', 'category', 'final_outcome', 'is_free', 'price', 'minutes', 'status'])]);
        if (! $preview) {
            $query->whereIn('related_course_series_id', $this->publicCourses()->select('id'));
        }
        $relations = $query->get()->groupBy('relation_type');
        $groups = [];
        foreach (CourseRelation::TYPES as $type => $label) {
            $links = [];
            foreach ($relations->get($type, collect()) as $relation) {
                $target = $relation->relatedCourse;
                if (! $target) {
                    continue;
                }
                $links[] = [
                    'title' => $target->title, 'outcome' => $target->final_outcome,
                    'reason' => $relation->description, 'minutes' => $target->minutes,
                    'access' => $target->is_free ? '完整免费' : '完整课程 · ¥'.$target->price,
                    'status' => $preview ? ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$target->status] : null,
                    'url' => route($preview ? 'courses.preview' : 'courses.show', $target),
                ];
            }
            if ($links) {
                $groups[] = ['type' => $type, 'label' => $label, 'links' => $links];
            }
        }

        return $groups;
    }
}
