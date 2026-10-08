<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Services\LegacyCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteOutlineSourceTest extends TestCase
{
    use RefreshDatabase;

    private function website(): CourseSeries
    {
        app(LegacyCourseImporter::class)->run();
        $course = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        foreach ($course->lessons()->get() as $lesson) {
            $lesson->update(['status' => 'published']);
        }
        $course->refresh()->update(['status' => 'published', 'is_free' => true, 'price' => 0]);

        return $course->fresh();
    }

    private function extraLesson(CourseSeries $course, string $status = 'published'): Lesson
    {
        return $course->lessons()->create([
            'slug' => 'acceptance', 'title' => '新增验收课时', 'position' => 6,
            'goal' => '核对最终网站成果', 'score' => 100, 'points' => 10,
            'steps' => [['title' => '核对成果', 'body' => '按完成标准亲自核对网站']],
            'prompt' => 'PRIVATE_DATABASE_PROMPT', 'content' => 'PRIVATE_DATABASE_CONTENT',
            'checks' => ['我已亲自核对网站成果'], 'status' => $status,
        ]);
    }

    public function test_six_published_lessons_are_visible_and_free_without_legacy_points_limits(): void
    {
        $course = $this->website();
        $extra = $this->extraLesson($course);
        $course->refresh()->update(['status' => 'published']);
        $this->assertSame(110, $course->lessons()->sum('points'));

        foreach (['pop', 'future'] as $theme) {
            config(['themes.active' => $theme]);
            $response = $this->get('/series/build-a-website')->assertOk()
                ->assertSee($extra->title)->assertSee('6个步骤')->assertSee('免费学习')
                ->assertDontSee('PRIVATE_DATABASE')->assertDontSee('免费试看第一课')
                ->assertViewHas('series', fn ($series) => $series['record_id'] === $course->id
                    && count($series['lessons']) === 6
                    && array_column($series['lessons'], 'slug') === $course->lessons()->pluck('slug')->all());
            $this->assertSame(0, substr_count($response->getContent(), 'aria-disabled="true"'));
            $response->assertSee(route('lessons.show', [$course, $extra->slug]));
        }
        $this->get('/lab')->assertViewHas('courses', fn ($courses) => $courses->contains('id', $course->id));
        $this->get('/lab/build-a-website/acceptance')->assertOk()->assertSee($extra->prompt);
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertOk()->assertSee('16.7%');
        $this->get('/series/build-a-website/lessons/server-and-ip/checklist')->assertOk();
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_six_lessons_can_use_existing_learning_routes_when_configuration_is_valid(): void
    {
        $course = $this->website();
        $extra = $this->extraLesson($course);
        $course->lessons()->where('slug', 'customize')->firstOrFail()->update(['points' => 10]);
        $course->refresh()->update(['status' => 'published']);

        $this->get('/series/build-a-website')->assertOk()->assertSee($extra->title)
            ->assertSee(route('lessons.show', [$course, $extra->slug]))
            ->assertViewHas('series', fn ($series) => count($series['lessons']) === 6);
        $this->get('/series/build-a-website/lessons/acceptance')->assertOk()->assertSee($extra->prompt);
        $this->get('/lab/build-a-website/acceptance')->assertOk()->assertSee($extra->prompt);
        $this->get('/series/build-a-website/lessons/acceptance/checklist')->assertOk()
            ->assertSee($extra->checks[0])->assertDontSee($extra->prompt);
    }

    public function test_database_drafts_and_archives_never_reappear_as_static_lesson_content(): void
    {
        $course = $this->website();
        $this->extraLesson($course, 'draft');
        $course->refresh()->update(['status' => 'published']);
        $first = $course->lessons()->where('slug', 'server-and-ip')->firstOrFail();
        $first->update(['title' => '数据库当前第一课']);
        $this->get('/series/build-a-website')->assertOk()->assertSee($first->title)
            ->assertDontSee('新增验收课时')->assertDontSee('PRIVATE_DATABASE')
            ->assertViewHas('series', fn ($series) => count($series['lessons']) === 5);

        $course->update(['status' => 'draft', 'title' => 'PRIVATE_DRAFT_COURSE']);
        $this->get('/series/build-a-website')->assertNotFound()->assertDontSee('PRIVATE_DRAFT_COURSE');
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertNotFound();

        $course->update(['status' => 'published']);
        $first->fresh()->update(['status' => 'archived']);
        $course->refresh()->update(['status' => 'published']);
        $this->get('/series/build-a-website')->assertOk()->assertDontSee($first->title)
            ->assertViewHas('series', fn ($series) => count($series['lessons']) === 4);
    }
}
