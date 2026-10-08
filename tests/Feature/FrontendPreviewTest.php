<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Services\FreeLabInstaller;
use App\Services\LegacyCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        app(LegacyCourseImporter::class)->run();
        $course = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        foreach ($course->lessons()->get() as $lesson) { $lesson->update(['status' => 'published']); }
        $course->refresh()->update(['status' => 'published', 'is_free' => true]);
    }

    public function test_public_frontend_pages_render(): void
    {
        foreach (['/', '/series', '/series/build-a-website', '/series/personal-intro-page', '/series/merge-csv-report', '/series/compare-prompts', '/pricing', '/live', '/questions', '/login', '/register'] as $path) {
            $this->get($path)->assertOk()->assertSee('AI100分');
        }
        $this->get('/series/remix-a-game')->assertNotFound();
    }

    public function test_free_lesson_includes_steps_and_a_downloadable_checklist(): void
    {
        $this->get('/series/build-a-website/lessons/server-and-ip')
            ->assertOk()->assertSee('free-prompt')->assertSee('data-free-progress', false)
            ->assertSee('10分钟搭建.com网站')->assertSee('购买服务器 [人]')
            ->assertSee('注册账号并准备费用')->assertSee('用 SSH 登录')
            ->assertSee('公网 IP')->assertSee('外部等待另计')
            ->assertViewHas('lessons', fn ($lessons) => $lessons->count() === 5)
            ->assertViewHas('lesson', fn ($lesson) => count($lesson->checks) === 3 && $lesson->minutes === 2);

        $this->get('/series/build-a-website/lessons/server-and-ip/checklist')
            ->assertOk()->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="lesson-checklist.md"')
            ->assertSee('我已确认服务器配置、计费方式和费用')
            ->assertSee('我能通过 SSH 或控制台登录并确认是自己的服务器');
    }

    public function test_question_topics_support_direct_links_and_invalid_queries_fall_back_safely(): void
    {
        $this->get('/questions?topic=memory')->assertOk()
            ->assertViewHas('selectedTopic', 'memory')
            ->assertSee('Agent 为什么忘记目标')
            ->assertSee('/lab?category=solve', false)
            ->assertSee('不代表原文提问、全网热度排名或实时统计');

        foreach (['/questions?topic=missing', '/questions?topic[]=memory', '/questions?topic=%3Cscript%3E'] as $path) {
            $this->get($path)->assertOk()->assertViewHas('selectedTopic', 'make-software')
                ->assertDontSee('<script>', false);
        }
    }

    public function test_unpublished_lesson_cannot_be_unlocked_by_frontend_parameters(): void
    {
        $course = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $course->lessons()->where('slug', 'domain')->firstOrFail()->update(['status' => 'draft']);
        $course->refresh()->update(['status' => 'published']);
        $this->get('/series/build-a-website/lessons/domain?is_free=1&purchased=1&subscription=active')
            ->assertNotFound()->assertDontSee('free-prompt')->assertDontSee('data-free-progress', false);

        $this->get('/series/build-a-website/lessons/domain/checklist?is_free=1')->assertNotFound();
    }

    public function test_lessons_are_scoped_to_their_series_and_missing_content_returns_404(): void
    {
        $this->get('/series/unknown')->assertNotFound()->assertSee('这条路径，暂时还没有。');
        $this->get('/series/build-a-miniapp/lessons/server-and-ip')->assertNotFound();
        $this->get('/series/build-a-website/lessons/unknown')->assertNotFound();
    }
}
