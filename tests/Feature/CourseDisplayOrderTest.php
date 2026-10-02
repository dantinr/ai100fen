<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Services\FreeLabInstaller;
use App\Support\FrontendCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDisplayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_lists_keep_the_original_order_until_configured_and_share_live_changes(): void
    {
        $original = app(FrontendCatalog::class)->all();
        $originalSlugs = array_column($original, 'slug');
        foreach (['/', '/series'] as $path) {
            $this->get($path)->assertOk()->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $originalSlugs);
        }

        $course = CourseSeries::create(['title' => 'PRIVATE edited draft content', 'slug' => 'remix-a-game', 'category' => 'create']);
        $this->assertSame(1000, $course->sort_order);
        $course->update(['sort_order' => 10]);
        CourseSeries::create(['title' => 'PRIVATE unlisted course', 'slug' => 'not-in-preview', 'category' => 'create', 'sort_order' => 0]);
        $expected = ['remix-a-game', ...array_values(array_diff($originalSlugs, ['remix-a-game']))];
        foreach (['/', '/series'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('PRIVATE')
                ->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $expected);
        }
        $this->get('/series/remix-a-game')->assertViewHas('series', fn ($series) => $series['available'] === false);
        $course->update(['sort_order' => 1000]);
        $this->get('/')->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $originalSlugs);
    }

    public function test_free_lab_uses_display_order_and_stable_ties_without_publishing_drafts_or_changing_lessons(): void
    {
        app(FreeLabInstaller::class)->install();
        $courses = CourseSeries::orderBy('id')->get();
        $lesson = $courses[2]->lessons->first();
        $lessonBefore = $lesson->getAttributes();
        $courses[2]->update(['sort_order' => 0]);
        $courses[1]->update(['sort_order' => 0]);
        $draft = CourseSeries::create(['title' => 'PRIVATE free draft', 'slug' => 'free-draft', 'category' => 'create', 'is_free' => true, 'sort_order' => 0]);

        $expected = [$courses[1]->id, $courses[2]->id, $courses[0]->id];
        $this->get('/free')->assertOk()->assertDontSee($draft->title)
            ->assertViewHas('courses', fn ($visible) => $visible->pluck('id')->all() === $expected);
        $this->assertSame('published', $courses[2]->fresh()->status);
        $this->assertSame($lessonBefore, $lesson->fresh()->getAttributes());
        $this->get('/free/'.$courses[2]->slug.'/'.$lesson->slug)->assertOk();
        $this->get('/free/free-draft/anything')->assertNotFound();
    }
}
