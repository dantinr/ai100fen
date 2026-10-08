<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Services\CourseRelationPresenter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CoursePreviewController extends Controller
{
    public function __invoke(CourseSeries $series, ?string $lessonSlug = null): Response
    {
        Gate::authorize('view', $series);
        $lessons = $series->lessons()->where('status', '!=', 'archived')->get();
        $lesson = $lessonSlug === null ? $lessons->first() : $series->lessons()->where('slug', $lessonSlug)->first();
        abort_if($lessonSlug !== null && ! $lesson, 404);

        return response()->view($lesson ? 'frontend.free.lesson' : 'frontend.preview.course', [
            'series' => $series, 'lessons' => $lessons, 'lesson' => $lesson,
            'isPreview' => true, 'progress' => null, 'score' => 0,
            'lessonPercent' => round(100 / max(1, $lessons->count()), 1),
            'relationGroups' => app(CourseRelationPresenter::class)->groups($series, preview: true),
        ])->header('Cache-Control', 'private, no-store');
    }
}
