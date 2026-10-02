<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\FreeLabInstaller;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FreeLabTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
    }

    public function test_guests_can_complete_all_three_full_free_tasks_and_download_resources(): void
    {
        $this->get('/free')->assertOk()->assertSee('完整免费')->assertSee('免费试看');
        $this->assertDatabaseCount('course_series', 3);
        $this->assertDatabaseCount('lessons', 3);
        foreach (CourseSeries::all() as $course) {
            $lesson = $course->lessons->first();
            $this->assertFalse($lesson->is_free); // Series-level free grants every lesson.
            $this->get("/free/{$course->slug}/{$lesson->slug}")->assertOk()->assertSee($lesson->goal)->assertSee('亲自验收');
            foreach ($lesson->resources as $resource) {
                $this->get("/free/{$course->slug}/{$lesson->slug}/resources/{$resource['name']}")
                    ->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="'.$resource['name'].'"')
                    ->assertSee($resource['content'], false);
            }
        }
    }

    public function test_full_free_and_paid_preview_are_distinct_and_drafts_are_denied(): void
    {
        $course = CourseSeries::first();
        $lesson = $course->lessons->first();
        $access = app(CourseAccessService::class);
        $course->update(['is_free' => false, 'price' => '100.00']);
        $this->assertFalse($access->canAccess($course, $lesson));
        $lesson->update(['is_free' => true]);
        $this->assertTrue($access->canAccess($course, $lesson));
        $this->get("/free/{$course->slug}/{$lesson->slug}?is_free=1&purchased=1&subscription=active")->assertNotFound();
        $this->get("/free/{$course->slug}/{$lesson->slug}/resources/index.html")->assertNotFound();
        $this->get('/free?q=个人介绍网页')->assertOk()->assertDontSee('Paul：可以从「'.$course->title);
        $course->update(['is_free' => true, 'status' => 'draft']);
        $this->assertFalse($access->canAccess($course, $lesson));
        $this->get("/free/{$course->slug}/{$lesson->slug}")->assertNotFound();
        $this->get('/series/build-a-website/lessons/domain/checklist?is_free=1')->assertForbidden();
    }

    public function test_lesson_and_resource_are_scoped_to_their_course_and_published_status(): void
    {
        $this->get('/free/personal-intro-page/merge-and-verify')->assertNotFound();
        $this->get('/free/personal-intro-page/make-and-check/resources/orders-a.csv')->assertNotFound();
        $course = CourseSeries::first();
        $lesson = $course->lessons->first();
        $lesson->update(['status' => 'draft']);
        $this->get("/free/{$course->slug}/{$lesson->slug}")->assertNotFound();
        $this->get("/free/{$course->slug}/{$lesson->slug}/resources/index.html")->assertNotFound();
        $this->get('/free')->assertDontSee($course->title);
    }

    public function test_guest_cannot_save_and_users_only_read_and_update_their_own_progress(): void
    {
        $path = '/free/personal-intro-page/make-and-check';
        $this->postJson($path.'/progress', ['checks' => [true, true, true]])->assertUnauthorized();
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        $this->actingAs($alice)->postJson($path.'/progress', ['checks' => [true, false, false], 'user_id' => $bob->id, 'completed_at' => now(), 'progress_percent' => 100])->assertOk()->assertJson(['progress_percent' => 33, 'completed' => false]);
        $this->assertDatabaseHas('lesson_progress', ['user_id' => $alice->id, 'progress_percent' => 33, 'completed_at' => null]);
        $this->actingAs($bob)->get($path)->assertOk()->assertSee('data-free-percent>0%', false);
        $this->get('/me')->assertSee('还没有保存免费任务进度');
        $this->postJson($path.'/progress', ['checks' => [false, false, true]])->assertOk();
        $this->actingAs($alice)->get($path)->assertSee('data-free-percent>33%', false);
        $this->get('/me')->assertSee('验收进度 33%');
        $this->assertDatabaseCount('lesson_progress', 2);
    }

    public function test_completion_is_validated_idempotent_and_can_be_corrected(): void
    {
        $this->actingAs(User::factory()->create());
        $path = '/free/compare-prompts/test-and-conclude/progress';
        foreach ([[true], [true, true, true, true], ['yes', true, true], [1 => true, 2 => true, 3 => true]] as $checks) {
            $this->postJson($path, ['checks' => $checks])->assertUnprocessable();
        }
        $this->postJson($path, ['checks' => [true, true, true]])->assertOk()->assertJson(['progress_percent' => 100, 'completed' => true, 'series_score' => 100]);
        $completed = LessonProgress::first()->completed_at->toISOString();
        $this->travel(1)->hours();
        $this->postJson($path, ['checks' => [true, true, true]])->assertOk();
        $this->assertDatabaseCount('lesson_progress', 1);
        $this->assertSame($completed, LessonProgress::first()->completed_at->toISOString());
        $this->post($path, ['checks' => ['1', '1', '0']])->assertRedirect()->assertSessionHas('free-progress-saved');
        $this->assertDatabaseHas('lesson_progress', ['progress_percent' => 66, 'completed_at' => null]);
    }

    public function test_paul_recommends_real_relevant_courses_and_handles_unknown_or_unsafe_text(): void
    {
        foreach (['网页作品' => '做一个能打开的个人介绍网页', '合并订单汇总' => '把几份 CSV 合成一张汇总表', '验证提示词对照实验' => '用对照实验选出更合适的 Prompt'] as $question => $title) {
            $this->get('/free?q='.urlencode($question))->assertOk()->assertSee('Paul：可以从「'.$title.'」开始。');
        }
        $this->get('/free?q='.urlencode('修理自行车'))->assertSee('目前这三个任务还接不住');
        $this->get('/free?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->getJson('/free?q='.str_repeat('x', 301))->assertUnprocessable();
    }

    public function test_home_intent_destinations_filter_real_courses_and_reject_invalid_categories(): void
    {
        foreach (['solve' => 'merge-csv-report', 'create' => 'personal-intro-page', 'explore' => 'compare-prompts'] as $category => $slug) {
            $this->get('/free?category='.$category)->assertOk()->assertViewHas('courses', fn ($courses) => $courses->count() === 1 && $courses->first()->slug === $slug);
        }
        $this->get('/free?category=solve&q=网页')->assertOk()->assertSee('这个方向暂时没有匹配的免费任务')->assertDontSee('Paul：可以从「做一个能打开的个人介绍网页');
        $this->getJson('/free?category=build')->assertUnprocessable();
        $this->getJson('/free?category[]=solve')->assertUnprocessable();
    }

    public function test_installer_is_idempotent_and_preserves_edits_and_progress(): void
    {
        $course = CourseSeries::first();
        $course->update(['title' => '人工修改后的课程']);
        $this->actingAs(User::factory()->create())->postJson('/free/personal-intro-page/make-and-check/progress', ['checks' => [true, true, true]])->assertOk();
        $this->assertSame(0, app(FreeLabInstaller::class)->install());
        $this->assertDatabaseCount('course_series', 3);
        $this->assertDatabaseCount('lesson_progress', 1);
        $this->assertSame('人工修改后的课程', $course->fresh()->title);
    }

    public function test_series_score_uses_configured_lesson_weights_without_a_ten_lesson_assumption(): void
    {
        $user = User::factory()->create();
        $series = CourseSeries::first();
        $first = $series->lessons->first();
        $first->update(['points' => 30, 'score' => 30]);
        $last = $first->replicate();
        $last->fill(['slug' => 'final-check', 'position' => 2, 'points' => 70, 'score' => 100])->save();
        $series->refresh()->update(['status' => 'published']); // Review the edited outline before allowing learning.
        $service = app(ProgressService::class);
        $service->save($user, $first, [true, true, true]);
        $this->assertSame(30, $service->seriesScore($user, $series));
        $service->save($user, $last, [true, true, true]);
        $this->assertSame(100, $service->seriesScore($user, $series));
    }

    public function test_publishing_requires_complete_constitution_fields(): void
    {
        $course = CourseSeries::first();
        $this->expectException(ValidationException::class);
        $course->update(['human_judgment_required' => true]);
    }

    public function test_future_theme_uses_same_content_and_login_returns_to_task_without_external_redirects(): void
    {
        config(['themes.active' => 'future']);
        $this->get('/free')->assertOk()->assertSee('data-theme="future"', false);
        $path = '/free/personal-intro-page/make-and-check';
        $this->get($path)->assertOk()->assertSee('data-page="lesson"', false)->assertSee('交给 Agent 的 Prompt');
        $this->get('/login?redirect='.urlencode($path))->assertOk()->assertSessionHas('url.intended', url($path));
        $this->get('/login?redirect=https://example.com')->assertSessionHas('url.intended', url($path));
        $user = User::factory()->create(['password' => 'Avalidpassword1']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Avalidpassword1'])->assertRedirect(url($path));
    }
}
