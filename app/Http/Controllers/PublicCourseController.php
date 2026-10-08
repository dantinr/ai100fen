<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Services\CourseRelationPresenter;
use Illuminate\Http\RedirectResponse;

class PublicCourseController extends Controller
{
    public function __invoke(CourseSeries $series, CourseRelationPresenter $relations): RedirectResponse
    {
        abort_unless($relations->publicCourses()->whereKey($series->id)->exists(), 404);
        return redirect()->route('series.show', $series->slug);
    }
}
