<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Services\FreeLabInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonVideoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        config(['player.demo_enabled' => false, 'player.license_domain' => '', 'player.license_key' => '']);
    }

    public function test_video_source_is_only_rendered_on_an_accessible_lesson(): void
    {
        $course = CourseSeries::where('slug', 'personal-intro-page')->firstOrFail();
        $lesson = $course->lessons()->firstOrFail();
        $source = 'https://media.example.com/private/lesson.m3u8?token=test';
        $lesson->update(['video_url' => $source]);

        $this->get(route('free.lesson', [$course, $lesson->slug]))->assertOk()
            ->assertSee('data-lesson-video', false)->assertSee($source)
            ->assertSee('亲自验收，才算做成')->assertDontSee('演示视频 · 非课程录播');
        $this->get(route('courses.show', $course))->assertOk()->assertDontSee($source);
        $course->update(['is_free' => false, 'price' => '100.00']);
        $this->get(route('free.lesson', [$course, $lesson->slug]))->assertNotFound()->assertDontSee($source);
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_demo_is_local_only_and_never_replaces_a_real_video(): void
    {
        config(['player.demo_enabled' => true]);
        $path = '/lab/personal-intro-page/make-and-check';
        app()->instance('env', 'production');
        $this->get($path)->assertOk()->assertDontSee('data-lesson-video', false)
            ->assertDontSee(config('player.demo_source'));
        app()->instance('env', 'local');
        $this->get($path)->assertOk()->assertSee('data-lesson-video', false)
            ->assertSee('演示视频 · 非课程录播')->assertSee(config('player.demo_source'));
        $lesson = CourseSeries::where('slug', 'personal-intro-page')->firstOrFail()->lessons()->firstOrFail();
        $lesson->update(['video_url' => 'https://media.example.com/real.m3u8']);
        $this->get($path)->assertOk()->assertSee('https://media.example.com/real.m3u8')
            ->assertDontSee(config('player.demo_source'))->assertDontSee('演示视频 · 非课程录播');
    }
}
