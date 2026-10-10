<?php

namespace App\Http\Controllers;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CourseAccessService;
use App\Services\CourseRelationPresenter;
use App\Services\FreeCourseRecommendationService;
use App\Services\ProgressService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FreeLabController extends Controller
{
    public function index(Request $request, FreeCourseRecommendationService $recommendations)
    {
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:300'], 'category' => ['nullable', 'string', Rule::in(['solve', 'create', 'explore'])]]);
        $category = $validated['category'] ?? null;
        $courses = CourseSeries::freeLab()->when($category, fn ($query) => $query->where('category', $category))
            ->with(['lessons' => fn ($query) => $query->where('status', 'published')])->displayOrder()->get();
        $question = $validated['q'] ?? '';

        return view('frontend.free.index', compact('courses', 'question', 'category') + [
            'recommendations' => $recommendations->recommend($question, $courses),
        ]);
    }

    private function lesson(CourseSeries $series, string $slug, CourseAccessService $access): Lesson
    {
        if (request()->routeIs('free.*')) {
            abort_unless(CourseSeries::freeLab()->whereKey($series->id)->exists(), 404);
        }
        $lesson = $series->lessons()->where('slug', $slug)->firstOrFail();
        abort_unless($access->canAccess($series, $lesson), 404);

        return $lesson;
    }

    public function show(Request $request, CourseSeries $series, string $lessonSlug, CourseAccessService $access, ProgressService $progressService)
    {
        $lesson = $this->lesson($series, $lessonSlug, $access);
        $progress = $request->user() ? LessonProgress::where('user_id', $request->user()->id)->where('lesson_id', $lesson->id)->first() : null;
        $lessons = $series->lessons()->where('status', 'published')->get();
        $score = $request->user() ? $progressService->seriesPercent($request->user(), $series) : 0;

        return response()->view('frontend.free.lesson', compact('series', 'lesson', 'progress', 'lessons', 'score') + [
            'lessonPercent' => round(100 / max(1, $series->lessons()->where('status', '!=', 'archived')->count()), 1),
            'completedLessonIds' => $request->user() ? $progressService->completedLessonIds($request->user(), $series) : [],
            'relationGroups' => app(CourseRelationPresenter::class)->groups($series),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function save(Request $request, CourseSeries $series, string $lessonSlug, CourseAccessService $access, ProgressService $progress)
    {
        $lesson = $this->lesson($series, $lessonSlug, $access);
        $data = $request->validate(['checks' => ['required', 'array', 'size:'.count($lesson->checks)], 'checks.*' => ['required', 'boolean']]);
        if (array_keys($data['checks']) !== range(0, count($lesson->checks) - 1)) {
            throw ValidationException::withMessages(['checks' => '验收清单不完整，请刷新后重试。']);
        }
        $record = $progress->save($request->user(), $lesson, array_map(fn ($check) => (bool) $check, $data['checks']));
        if ($request->expectsJson()) {
            return response()->json(['progress_percent' => $record->progress_percent, 'completed' => $record->completed_at !== null, 'series_percent' => $progress->seriesPercent($request->user(), $series)]);
        }

        return back()->with('free-progress-saved', true);
    }

    public function download(CourseSeries $series, string $lessonSlug, string $resource, CourseAccessService $access)
    {
        $lesson = $this->lesson($series, $lessonSlug, $access);
        $file = collect($lesson->resources)->firstWhere('name', $resource);
        abort_unless($file && preg_match('/\A[a-z0-9][a-z0-9._-]*\z/', $file['name']), 404);

        return response($file['content'])->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="'.$file['name'].'"')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    public function showCourse(Request $request, string $slug, string $lessonSlug, CourseAccessService $access, ProgressService $progress)
    {
        return $this->show($request, CourseSeries::where('slug', $slug)->firstOrFail(), $lessonSlug, $access, $progress);
    }

    public function saveCourse(Request $request, string $slug, string $lessonSlug, CourseAccessService $access, ProgressService $progress)
    {
        return $this->save($request, CourseSeries::where('slug', $slug)->firstOrFail(), $lessonSlug, $access, $progress);
    }

    public function downloadCourse(string $slug, string $lessonSlug, string $resource, CourseAccessService $access)
    {
        return $this->download(CourseSeries::where('slug', $slug)->firstOrFail(), $lessonSlug, $resource, $access);
    }

    public function checklistCourse(string $slug, string $lessonSlug, CourseAccessService $access)
    {
        $lesson = $this->lesson(CourseSeries::where('slug', $slug)->firstOrFail(), $lessonSlug, $access);
        $body = '# '.$lesson->title."：验收清单\n\n".$lesson->goal."\n\n";
        foreach ($lesson->checks as $check) {
            $body .= '- [ ] '.$check."\n";
        }

        return response($body)->header('Content-Type', 'text/markdown; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="lesson-checklist.md"')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
