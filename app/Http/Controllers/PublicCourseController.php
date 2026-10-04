<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Services\CourseRelationPresenter;
use App\Services\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PublicCourseController extends Controller
{
    public function __invoke(CourseSeries $series, CourseRelationPresenter $relations): View|RedirectResponse
    {
        abort_unless($relations->publicCourses()->whereKey($series->id)->exists(), 404);
        if ($series->slug === 'build-a-website' && $series->minutes === 10
            && CourseSeries::freeLab()->whereKey($series->id)->exists()) {
            return redirect()->route('series.show', $series->slug);
        }
        $lessons = $series->lessons()->where('status', 'published')->get(['id', 'course_series_id', 'slug', 'title', 'position', 'minutes', 'goal']);

        return view('frontend.course', [
            'series' => $series, 'lessons' => $lessons,
            'completedLessonIds' => request()->user() ? app(ProgressService::class)->completedLessonIds(request()->user(), $series) : [],
            'relationGroups' => $relations->groups($series),
        ]);
    }
}
