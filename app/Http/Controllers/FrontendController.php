<?php

namespace App\Http\Controllers;

use App\Support\FrontendCatalog;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FrontendController extends Controller
{
    public function __construct(private readonly FrontendCatalog $catalog) {}

    public function home(): View
    {
        return view('frontend.home', ['series' => $this->catalog->all()]);
    }

    public function index(): View
    {
        return view('frontend.catalog', ['series' => $this->catalog->all()]);
    }

    public function series(string $slug): View
    {
        return view('frontend.series', ['series' => $this->catalog->find($slug)]);
    }

    public function lesson(string $slug, string $lessonSlug): View
    {
        $series = $this->catalog->find($slug);
        $lesson = $this->catalog->findLesson($series, $lessonSlug);
        $canPreview = $this->catalog->canPreview($series, $lesson);

        return view('frontend.lesson', [
            'series' => $series, 'lesson' => $lesson, 'canPreview' => $canPreview,
            'content' => $canPreview ? $this->catalog->previewContent($series, $lesson) : null,
        ]);
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

    public function live(): View
    {
        return view('frontend.live');
    }

    public function me(): View
    {
        return view('frontend.me', ['series' => $this->catalog->all()]);
    }

    public function login(): View
    {
        return view('frontend.auth', ['register' => false]);
    }

    public function register(): View
    {
        return view('frontend.auth', ['register' => true]);
    }
}
