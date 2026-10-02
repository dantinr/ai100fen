<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Services\CourseRelationPresenter;
use Illuminate\View\View;

class PublicCourseController extends Controller
{
    public function __invoke(CourseSeries $series, CourseRelationPresenter $relations): View
    {
        abort_unless($relations->publicCourses()->whereKey($series->id)->exists(), 404);
        $lessons = $series->lessons()->where('status', 'published')->get(['id', 'course_series_id', 'slug', 'title', 'position', 'minutes', 'goal']);

        return view('frontend.course', [
            'series' => $series, 'lessons' => $lessons,
            'relationGroups' => $relations->groups($series),
        ]);
    }
}
