<?php

namespace App\Services;

use App\Models\CourseSeries;
use Illuminate\Support\Facades\Schema;

class CourseDisplayOrder
{
    /** Keep the preview's content and access rules; read only order by matching slug. */
    public function previewCourses(array $courses): array
    {
        if (! Schema::hasColumn('course_series', 'sort_order')) {
            return $courses;
        }

        $orders = CourseSeries::whereIn('slug', array_column($courses, 'slug'))->pluck('sort_order', 'slug');

        // Collection sorting is stable: equal values retain the curated order.
        return collect($courses)->sortBy(fn (array $course) => $orders[$course['slug']] ?? 1000)->values()->all();
    }
}
