<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class CoursePreviewController extends Controller
{
    public function __invoke(CourseSeries $series, ?string $lessonSlug = null): Response
    {
        Gate::authorize('view', $series);
        $lessons = $series->lessons()->get();
        $lesson = $lessonSlug === null ? $lessons->first() : $lessons->firstWhere('slug', $lessonSlug);
        abort_if($lessonSlug !== null && ! $lesson, 404);

        return response()->view($lesson ? 'frontend.free.lesson' : 'frontend.preview.course', [
            'series' => $series, 'lessons' => $lessons, 'lesson' => $lesson,
            'isPreview' => true, 'progress' => null, 'score' => 0,
        ])->header('Cache-Control', 'private, no-store');
    }
}
