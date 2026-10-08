<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Services\LegacyCourseImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(LegacyCourseImporter::class)->run();
        $course = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $course->refresh()->update(['status' => 'published']);
    }

    public function test_default_theme_and_independent_page_variants_render(): void
    {
        config(['themes.active' => 'pop']);

        foreach (['/' => 'home', '/series' => 'series', '/series/build-a-website/lessons/server-and-ip' => 'lesson', '/questions' => 'question-pool', '/login' => 'account', '/pricing' => 'checkout'] as $url => $variant) {
            $this->get($url)->assertOk()
                ->assertSee('data-theme="pop"', false)
                ->assertSee('data-page="'.$variant.'"', false);
        }
    }

    public function test_invalid_theme_values_fall_back_without_affecting_access(): void
    {
        foreach (['unknown', '../../outside', '', null, ['future']] as $invalid) {
            config(['themes.active' => $invalid]);
            $this->get('/')->assertOk()->assertSee('data-theme="pop"', false);
        }

        $this->get('/series/build-a-website/lessons/domain?theme=future&is_free=1')
            ->assertNotFound()->assertDontSee('free-prompt');
        $this->get('/series/build-a-website/lessons/domain/checklist')->assertNotFound();
    }

    public function test_future_theme_uses_the_same_pages_and_business_behavior(): void
    {
        config(['themes.active' => 'future']);

        foreach (['/', '/series', '/series/build-a-website', '/series/build-a-website/lessons/server-and-ip', '/questions', '/login', '/pricing'] as $url) {
            $this->get($url)->assertOk()->assertSee('data-theme="future"', false);
        }

        $this->get('/series/build-a-website/lessons/server-and-ip')
            ->assertSee('data-free-progress', false)->assertSee('free-prompt');
        $this->get('/pricing')->assertSee('100')->assertSee('299')->assertSee('购买暂未开放');
        $this->get('/series/build-a-website/lessons/domain/checklist')->assertNotFound();

        config(['themes.active' => 'pop']);
        $this->get('/?theme=future')->assertSee('data-theme="pop"', false);
    }

    public function test_built_pages_load_only_the_selected_theme_stylesheet(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        foreach (['pop' => 'future', 'future' => 'pop'] as $active => $other) {
            config(['themes.active' => $active]);
            $this->get('/')->assertOk()
                ->assertSee($manifest['resources/css/themes/'.$active.'.css']['file'], false)
                ->assertDontSee($manifest['resources/css/themes/'.$other.'.css']['file'], false);
        }
    }
}
