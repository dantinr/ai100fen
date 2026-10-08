<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Services\CourseCatalog;
use App\Services\CourseRelationPresenter;
use App\Services\LiveSchedule;
use App\Services\ProgressService;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(private readonly CourseCatalog $catalog) {}

    public function home(): View
    {
        return view('frontend.home', ['series' => $this->catalog->all()]);
    }

    public function index(): View
    {
        return view('frontend.catalog', ['series' => $this->catalog->all()]);
    }

    public function series(string $slug, ProgressService $progress): View
    {
        $series = $this->catalog->find($slug);
        $record = CourseSeries::findOrFail($series['record_id']);

        return view('frontend.series', [
            'series' => $series,
            'serverScore' => request()->user() ? $progress->seriesPercent(request()->user(), $record) : 0,
            'completedLessonSlugs' => request()->user() ? $record->lessons()
                ->whereIn('id', $progress->completedLessonIds(request()->user(), $record))->pluck('slug')->all() : [],
            'relationGroups' => app(CourseRelationPresenter::class)->groups($record),
        ]);
    }

    public function pricing(): View
    {
        return view('frontend.pricing');
    }

    public function live(LiveSchedule $schedule): View
    {
        $month = request()->query('month');

        return view('frontend.live', $schedule->forMonth(is_string($month) ? $month : null));
    }
}
