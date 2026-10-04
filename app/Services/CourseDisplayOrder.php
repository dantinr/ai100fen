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

        $columns = ['id', 'slug', 'sort_order', 'is_free', 'price', 'minutes', 'status'];
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

        $record = CourseSeries::where('slug', $course['slug'])->first([
            'id', 'slug', 'cover', 'title', 'minutes', 'is_free', 'price', 'status',
            'final_outcome', 'completion_criteria',
        ]);
        $course = $this->withCover($course, $record);
        if ($course['is_free'] ?? false) {
            $course['record_id'] = $record->id;
            $course['title'] = $record->title;
            $course['minutes'] = $record->minutes;
            $course['outcome'] = $record->final_outcome;
            $course['deliverables'] = $record->completion_criteria;
            // The introduction only reads public outline metadata, never lesson content.
            $course['lessons'] = $record->lessons()->where('status', 'published')
                ->get(['slug', 'title', 'goal', 'score', 'minutes'])
                ->map(fn ($lesson) => [
                    'slug' => $lesson->slug, 'title' => $lesson->title, 'summary' => $lesson->goal,
                    'score' => $lesson->score, 'minutes' => $lesson->minutes, 'is_free' => true,
                ])->all();
        }

        return $course;
    }

    private function withCover(array $course, ?CourseSeries $record): array
    {
        $course['cover_url'] = $record?->coverUrl();
        if ($course['slug'] === 'build-a-website' && $record?->is_free && $record->minutes === 10
            && CourseSeries::freeLab()->whereKey($record->id)->exists()) {
            $course['is_free'] = true;
            $course['price'] = $record->price;
        }

        return $course;
    }
}
