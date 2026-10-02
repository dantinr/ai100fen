<?php

namespace App\Services;

use App\Models\CourseSeries;
use Illuminate\Support\Facades\Schema;

class CourseDisplayOrder
{
    /** Keep the preview's content and access rules; read only display fields by matching slug. */
    public function previewCourses(array $courses): array
    {
        if (! Schema::hasColumn('course_series', 'sort_order')) {
            return $courses;
        }

        $columns = ['slug', 'sort_order'];
        if (Schema::hasColumn('course_series', 'cover')) {
            $columns[] = 'cover';
        }
        $records = CourseSeries::whereIn('slug', array_column($courses, 'slug'))->get($columns)->keyBy('slug');

        // Collection sorting is stable: equal values retain the curated order.
        return collect($courses)
            ->sortBy(fn (array $course) => $records->get($course['slug'])?->sort_order ?? 1000)
            ->map(fn (array $course) => $this->withCover($course, $records->get($course['slug'])))
            ->values()->all();
    }

    public function previewCourse(array $course): array
    {
        if (! Schema::hasColumn('course_series', 'cover')) {
            return $course;
        }

        return $this->withCover($course, CourseSeries::where('slug', $course['slug'])->first(['cover']));
    }

    private function withCover(array $course, ?CourseSeries $record): array
    {
        $course['cover_url'] = $record?->coverUrl();

        return $course;
    }
}
