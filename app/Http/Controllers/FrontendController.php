<?php

namespace App\Http\Controllers;

use App\Services\CourseDisplayOrder;
use App\Services\CourseRelationPresenter;
use App\Services\LiveSchedule;
use App\Services\ProgressService;
use App\Support\FrontendCatalog;
use App\Models\CourseSeries;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(private readonly FrontendCatalog $catalog, private readonly CourseDisplayOrder $displayOrder) {}

    public function home(): View
    {
        return view('frontend.home', ['series' => $this->displayOrder->previewCourses($this->catalog->all())]);
    }

    public function index(): View
    {
        return view('frontend.catalog', ['series' => $this->displayOrder->previewCourses($this->catalog->all())]);
    }

    public function series(string $slug, ProgressService $progress): View
    {
        $series = $this->displayOrder->previewCourse($this->catalog->find($slug));
        $serverScore = null;
        $completedLessonSlugs = [];
        if (isset($series['record_id'])) {
            $record = CourseSeries::findOrFail($series['record_id']);
            $serverScore = request()->user()
                ? $progress->seriesScore(request()->user(), $record)
                : 0;
            if (request()->user()) {
                $completedLessonSlugs = $record->lessons()->whereIn('id', $progress->completedLessonIds(request()->user(), $record))->pluck('slug')->all();
            }
        }

        return view('frontend.series', ['series' => $series, 'serverScore' => $serverScore,
            'completedLessonSlugs' => $completedLessonSlugs,
            'relationGroups' => app(CourseRelationPresenter::class)->forLegacy($slug)]);
    }

    public function lesson(string $slug, string $lessonSlug): View|RedirectResponse
    {
        if ($this->completeWebsiteCourse($slug)) {
            return redirect()->route('free.lesson', [$slug, $lessonSlug]);
        }
        $series = $this->catalog->find($slug);
        $lesson = $this->catalog->findLesson($series, $lessonSlug);
        $canPreview = $this->catalog->canPreview($series, $lesson);

        return view('frontend.lesson', [
            'series' => $series, 'lesson' => $lesson, 'canPreview' => $canPreview,
            'content' => $canPreview ? $this->catalog->previewContent($series, $lesson) : null,
        ]);
    }

    private function completeWebsiteCourse(string $slug): bool
    {
        return $slug === 'build-a-website' && Schema::hasTable('course_series')
            && CourseSeries::freeLab()->where('slug', $slug)->where('minutes', 10)->exists();
    }

    public function checklist(string $slug, string $lessonSlug): Response
    {
        $series = $this->catalog->find($slug);
        $lesson = $this->catalog->findLesson($series, $lessonSlug);
        $content = $this->catalog->previewContent($series, $lesson);
        $body = '# '.$lesson['title']."：验收清单\n\n".$content['goal']."\n\n";

        foreach ($content['checks'] as $check) {
            $body .= '- [ ] '.$check."\n";
        }

        return response($body, 200, [
            'Content-Type' => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="lesson-checklist.md"',
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
