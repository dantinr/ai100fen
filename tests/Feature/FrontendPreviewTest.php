<?php

namespace Tests\Feature;

use Tests\TestCase;

class FrontendPreviewTest extends TestCase
{
    public function test_public_frontend_pages_render(): void
    {
        foreach (['/', '/series', '/series/build-a-website', '/series/add-website-support', '/series/build-a-miniapp', '/series/analyze-your-content', '/series/plan-your-next-creation', '/series/merge-excel-files', '/series/create-a-presentation', '/series/build-a-work-tool', '/series/build-your-own-software', '/series/build-your-own-agent', '/series/remix-a-game', '/series/organize-your-files', '/series/write-a-research-report', '/series/build-personal-digital-assets', '/series/build-a-knowledge-library', '/series/turn-meetings-into-actions', '/pricing', '/live', '/questions', '/login', '/register'] as $path) {
            $this->get($path)->assertOk()->assertSee('AI100分');
        }
    }

    public function test_free_lesson_includes_steps_and_a_downloadable_checklist(): void
    {
        $this->get('/series/build-a-website/lessons/server-and-ip')
            ->assertOk()->assertSee('lesson-prompt')->assertSee('data-acceptance', false)
            ->assertSee('10分钟搭建.com网站')->assertSee('购买服务器 [人]')
            ->assertSee('注册账号并准备费用')->assertSee('用 SSH 登录')
            ->assertSee('公网 IP')->assertSee('外部等待另计')
            ->assertViewHas('series', fn ($series) => count($series['lessons']) === 5 && $series['minutes'] === 10)
            ->assertViewHas('content', fn ($content) => count($content['checks']) === 3 && $content['minutes'] === 2);

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
            ->assertSee('/free?category=solve', false)
            ->assertSee('不代表原文提问、全网热度排名或实时统计');

        foreach (['/questions?topic=missing', '/questions?topic[]=memory', '/questions?topic=%3Cscript%3E'] as $path) {
            $this->get($path)->assertOk()->assertViewHas('selectedTopic', 'make-software')
                ->assertDontSee('<script>', false);
        }
    }

    public function test_unpublished_lesson_cannot_be_unlocked_by_frontend_parameters(): void
    {
        $this->get('/series/build-a-website/lessons/domain?is_free=1&purchased=1&subscription=active')
            ->assertOk()->assertSee('这一步，正在准备中。')->assertDontSee('lesson-prompt')
            ->assertDontSee('data-acceptance', false);

        $this->get('/series/build-a-website/lessons/domain/checklist?is_free=1')->assertForbidden();
    }

    public function test_lessons_are_scoped_to_their_series_and_missing_content_returns_404(): void
    {
        $this->get('/series/unknown')->assertNotFound()->assertSee('这条路径，暂时还没有。');
        $this->get('/series/build-a-miniapp/lessons/server-and-ip')->assertNotFound();
        $this->get('/series/build-a-website/lessons/unknown')->assertNotFound();
    }
}
