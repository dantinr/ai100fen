<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Models\CourseSeries;
use App\Models\User;
use App\Services\FreeLabInstaller;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseDisplayOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_courses_share_live_order_changes_and_exclude_drafts(): void
    {
        app(FreeLabInstaller::class)->install();
        $courses = CourseSeries::orderBy('id')->get();
        $originalSlugs = $courses->pluck('slug')->all();
        foreach (['/', '/series'] as $path) {
            $this->get($path)->assertOk()->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $originalSlugs);
        }

        $course = $courses->last();
        $this->assertSame(1000, $course->sort_order);
        $course->update(['sort_order' => 10]);
        CourseSeries::create(['title' => 'PRIVATE unlisted course', 'slug' => 'not-in-preview', 'category' => 'create', 'sort_order' => 0,
            'recommendation_keywords' => ['PRIVATE unlisted keyword']]);
        $expected = [$course->slug, ...array_values(array_diff($originalSlugs, [$course->slug]))];
        foreach (['/', '/series'] as $path) {
            $this->get($path)->assertOk()->assertDontSee('PRIVATE')
                ->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $expected);
        }
        $this->get('/series/not-in-preview')->assertNotFound();
        $course->update(['sort_order' => 1000]);
        $this->get('/')->assertViewHas('series', fn ($courses) => array_column($courses, 'slug') === $originalSlugs);
    }

    public function test_admin_keywords_reach_public_cards_and_search_without_publishing_draft_content(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();

        app(FreeLabInstaller::class)->install();
        $course = CourseSeries::first();
        CourseSeries::create(['title' => 'PRIVATE draft title', 'slug' => 'private-course', 'category' => 'create',
            'description' => 'PRIVATE draft body']);
        $keywords = ['游戏改造', 'Agent 协作', '<script>alert("tag")</script>'];
        $editor = Livewire::test(EditCourseSeries::class, ['record' => $course->slug]);
        $editor->fillForm(['recommendation_keywords' => $keywords])->call('save')->assertHasNoFormErrors();
        $this->assertSame($keywords, $course->fresh()->recommendation_keywords);
        $this->assertSame('published', $course->fresh()->status);

        auth()->logout();
        foreach (['pop', 'future'] as $theme) {
            config(['themes.active' => $theme]);
            foreach (['/', '/series'] as $path) {
                $response = $this->get($path)->assertOk()->assertDontSee('PRIVATE')
                    ->assertViewHas('series', fn ($courses) => collect($courses)->firstWhere('slug', $course->slug)['keywords'] === $keywords);
                $this->assertStringContainsString('<span class="course-keyword">'.e($keywords[0]).'</span>', $response->getContent());
                $this->assertStringContainsString(implode(' ', array_map('e', $keywords)), $response->getContent());
                $response->assertSee($keywords[2])->assertDontSee($keywords[2], false);
            }
        }

        $this->actingAs($admin);
        $editor->fillForm(['recommendation_keywords' => []])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertViewHas('series', fn ($courses) => collect($courses)->firstWhere('slug', $course->slug)['keywords'] === []);
        $this->assertSame([], $course->fresh()->recommendation_keywords);
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
        $this->get('/lab')->assertOk()->assertDontSee($draft->title)
            ->assertViewHas('courses', fn ($visible) => $visible->pluck('id')->all() === $expected);
        $this->assertSame('published', $courses[2]->fresh()->status);
        $this->assertSame($lessonBefore, $lesson->fresh()->getAttributes());
        $this->get('/lab/'.$courses[2]->slug.'/'.$lesson->slug)->assertOk();
        $this->get('/lab/lab-draft/anything')->assertNotFound();
    }
}
