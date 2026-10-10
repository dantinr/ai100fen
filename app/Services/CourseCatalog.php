<?php

namespace App\Services;

use App\Models\CourseSeries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class CourseCatalog
{
    public function publicCourses(): Builder
    {
        return CourseSeries::where('status', 'published')
            ->whereHas('lessons', fn ($query) => $query->where('status', 'published'));
    }

    public function all(): array
    {
        if (! Schema::hasTable('course_series')) {
            return [];
        }

        return $this->publicCourses()->displayOrder()->with(['lessons' => fn ($query) => $query
            ->where('status', 'published')->select(['id', 'course_series_id', 'slug', 'title', 'position', 'goal', 'minutes', 'is_free'])])
            ->get()->map(fn ($course) => $this->present($course))->all();
    }

    public function find(string $slug): array
    {
        abort_unless(Schema::hasTable('course_series'), 404);
        $course = $this->publicCourses()->where('slug', $slug)->with(['lessons' => fn ($query) => $query
            ->where('status', 'published')->select(['id', 'course_series_id', 'slug', 'title', 'position', 'goal', 'minutes', 'is_free'])])->firstOrFail();

        return $this->present($course);
    }

    public function starter(): ?array
    {
        foreach ($this->all() as $course) {
            foreach ($course['lessons'] as $lesson) {
                if ($lesson['is_free']) {
                    return ['title' => $course['title'], 'url' => route('lessons.show', ['slug' => $course['slug'], 'lessonSlug' => $lesson['slug']])];
                }
            }
        }

        return null;
    }

    private function present(CourseSeries $course): array
    {
        $labels = ['solve' => '解决一个问题', 'create' => '创作一个作品', 'explore' => '探索一个可能'];
        $lessons = $course->lessons->map(fn ($lesson) => [
            'id' => $lesson->id, 'slug' => $lesson->slug, 'title' => $lesson->title,
            'summary' => $lesson->goal, 'minutes' => $lesson->minutes,
            'is_free' => $course->is_free || $lesson->is_free,
        ])->all();

        return [
            'record_id' => $course->id, 'slug' => $course->slug, 'title' => $course->title,
            'question' => $course->user_intent, 'outcome' => $course->final_outcome,
            'description' => $course->final_outcome, 'detail_description' => $course->description,
            'minutes' => $course->minutes, 'category' => $course->category,
            'category_label' => $labels[$course->category],
            'tag' => $course->recommendation_keywords[0] ?? $labels[$course->category],
            'keywords' => $course->recommendation_keywords ?? [], 'cover_url' => $course->coverUrl(),
            'image' => match ($course->category) {
                'solve' => 'spreadsheet', 'explore' => 'analysis', default => 'website'
            },
            'available' => true, 'is_free' => $course->is_free, 'price' => $course->price,
            'prerequisites' => $course->prerequisites ?? [], 'deliverables' => $course->completion_criteria,
            'lessons' => $lessons,
        ];
    }
}
