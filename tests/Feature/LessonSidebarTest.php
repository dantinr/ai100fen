<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\User;
use App\Services\FreeLabInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_uses_current_lesson_objectives_and_keeps_course_outline_separate(): void
    {
        app(FreeLabInstaller::class)->install();
        $course = CourseSeries::first();
        $lesson = $course->lessons()->firstOrFail();
        $lesson->update(['objectives' => ['我的网页可以打开', '页面显示我的真实内容', '修改后刷新可以看到变化']]);
        $course->update(['objectives' => ['整门课程的额外目标']]);

        $response = $this->get(route('free.lesson', [$course, $lesson->slug]))->assertOk();
        $sidebar = $this->sidebar($response->getContent());
        foreach ($lesson->objectives as $objective) {
            $this->assertStringContainsString($objective, $sidebar);
        }
        $this->assertStringContainsString('目标 3', $sidebar);
        $this->assertStringNotContainsString('整门课程的额外目标', $sidebar);
        $this->assertStringNotContainsString('free-outline-link', $sidebar);
        $response->assertSee('lesson-course-outline-title', false)->assertSee('整门课程的额外目标');

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $preview = $this->actingAs($admin)->get(route('courses.preview', [$course, $lesson->slug]))->assertOk();
        $this->assertStringContainsString('目标 3', $this->sidebar($preview->getContent()));
        $preview->assertDontSee('data-free-progress', false)->assertDontSee('data-free-percent', false);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_sidebar_falls_back_to_the_lesson_goal_without_inventing_objectives(): void
    {
        app(FreeLabInstaller::class)->install();
        $course = CourseSeries::first();
        $lesson = $course->lessons()->firstOrFail();
        $lesson->update(['objectives' => []]);
        $response = $this->get(route('free.lesson', [$course, $lesson->slug]))->assertOk();
        $sidebar = $this->sidebar($response->getContent());
        $this->assertStringContainsString($lesson->goal, $sidebar);
        $this->assertStringContainsString('目标 1', $sidebar);
        $this->assertStringNotContainsString('目标 2', $sidebar);
        $this->assertNull($lesson->fresh()->objectives ?: null);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    private function sidebar(string $html): string
    {
        preg_match('/<aside class="free-sidebar"[^>]*>(.*?)<\/aside>/s', $html, $matches);

        return $matches[1] ?? '';
    }
}
