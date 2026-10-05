<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\FreeLabInstaller;
use App\Services\ProgressService;
use App\Support\LegacyCourseDefinitions;
use App\Support\WebsiteSetupLessons;
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
        $this->get('/lab')->assertOk()->assertSee('完整免费')->assertSee('免费试看');
        $this->assertDatabaseCount('course_series', 3);
        $this->assertDatabaseCount('lessons', 3);
        foreach (CourseSeries::all() as $course) {
            $lesson = $course->lessons->first();
            $this->assertFalse($lesson->is_free); // Series-level free grants every lesson.
            $this->get("/lab/{$course->slug}/{$lesson->slug}")->assertOk()->assertSee($lesson->goal)->assertSee('亲自验收');
            foreach ($lesson->resources as $resource) {
                $this->get("/lab/{$course->slug}/{$lesson->slug}/resources/{$resource['name']}")
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
        $this->get("/lab/{$course->slug}/{$lesson->slug}?is_free=1&purchased=1&subscription=active")->assertNotFound();
        $this->get("/lab/{$course->slug}/{$lesson->slug}/resources/index.html")->assertNotFound();
        $this->get('/lab?q=个人介绍网页')->assertOk()->assertDontSee('Z：可以从「'.$course->title);
        $course->update(['is_free' => true, 'status' => 'draft']);
        $this->assertFalse($access->canAccess($course, $lesson));
        $this->get("/lab/{$course->slug}/{$lesson->slug}")->assertNotFound();
        $this->get('/series/build-a-website/lessons/domain/checklist?is_free=1')->assertForbidden();
    }

    public function test_lesson_and_resource_are_scoped_to_their_course_and_published_status(): void
    {
        $this->get('/lab/personal-intro-page/merge-and-verify')->assertNotFound();
        $this->get('/lab/personal-intro-page/make-and-check/resources/orders-a.csv')->assertNotFound();
        $course = CourseSeries::first();
        $lesson = $course->lessons->first();
        $lesson->update(['status' => 'draft']);
        $this->get("/lab/{$course->slug}/{$lesson->slug}")->assertNotFound();
        $this->get("/lab/{$course->slug}/{$lesson->slug}/resources/index.html")->assertNotFound();
        $this->get('/lab')->assertDontSee($course->title);
    }

    public function test_guest_cannot_save_and_users_only_read_and_update_their_own_progress(): void
    {
        $path = '/lab/personal-intro-page/make-and-check';
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
        $path = '/lab/compare-prompts/test-and-conclude/progress';
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

    public function test_completion_marks_only_use_current_users_saved_acceptance_and_can_be_removed(): void
    {
        $course = CourseSeries::where('slug', 'personal-intro-page')->firstOrFail();
        $lesson = $course->lessons()->firstOrFail();
        $path = route('free.lesson', [$course, $lesson->slug]);
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $this->get($path)->assertOk()->assertViewHas('completedLessonIds', []);
        $this->actingAs($alice)->postJson($path.'/progress', ['checks' => [true, true, true]])->assertOk();
        $this->get($path)->assertOk()->assertViewHas('completedLessonIds', [$lesson->id]);
        $this->get(route('courses.show', $course))->assertOk()->assertViewHas('completedLessonIds', [$lesson->id]);

        $this->actingAs($bob)->get($path)->assertOk()->assertViewHas('completedLessonIds', []);
        $this->get(route('courses.show', $course))->assertOk()->assertViewHas('completedLessonIds', []);
        $this->actingAs($alice)->postJson($path.'/progress', ['checks' => [true, false, true]])
            ->assertOk()->assertJson(['completed' => false]);
        $this->get($path)->assertOk()->assertViewHas('completedLessonIds', []);
        $this->get(route('courses.show', $course))->assertOk()->assertViewHas('completedLessonIds', []);
    }

    public function test_paul_recommends_real_relevant_courses_and_handles_unknown_or_unsafe_text(): void
    {
        foreach (['网页作品' => '做一个能打开的个人介绍网页', '合并订单汇总' => '把几份 CSV 合成一张汇总表', '验证提示词对照实验' => '用对照实验选出更合适的 Prompt'] as $question => $title) {
            $this->get('/lab?q='.urlencode($question))->assertOk()->assertSee('Z：可以从「'.$title.'」开始。');
        }
        $this->get('/lab?q='.urlencode('修理自行车'))->assertSee('目前这三个任务还接不住');
        $this->get('/lab?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
        $this->getJson('/lab?q='.str_repeat('x', 301))->assertUnprocessable();
    }

    public function test_home_intent_destinations_filter_real_courses_and_reject_invalid_categories(): void
    {
        foreach (['solve' => 'merge-csv-report', 'create' => 'personal-intro-page', 'explore' => 'compare-prompts'] as $category => $slug) {
            $this->get('/lab?category='.$category)->assertOk()->assertViewHas('courses', fn ($courses) => $courses->count() === 1 && $courses->first()->slug === $slug);
        }
        $this->get('/lab?category=solve&q=网页')->assertOk()->assertSee('这个方向暂时没有匹配的免费任务')->assertDontSee('Z：可以从「做一个能打开的个人介绍网页');
        $this->getJson('/lab?category=build')->assertUnprocessable();
        $this->getJson('/lab?category[]=solve')->assertUnprocessable();
    }

    public function test_installer_is_idempotent_and_preserves_edits_and_progress(): void
    {
        $course = CourseSeries::first();
        $course->update(['title' => '人工修改后的课程']);
        $this->actingAs(User::factory()->create())->postJson('/lab/personal-intro-page/make-and-check/progress', ['checks' => [true, true, true]])->assertOk();
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

    public function test_archived_lessons_keep_records_without_blocking_free_lab_or_diluting_active_scores(): void
    {
        $course = CourseSeries::first();
        $active = $course->lessons()->first();
        $user = User::factory()->create();
        $archived = $active->replicate();
        $archived->fill(['slug' => 'old-stage', 'position' => 10, 'points' => 10, 'score' => 10, 'status' => 'archived'])->save();
        $oldProgress = LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $archived->id,
            'checks' => [true, true, true], 'progress_percent' => 100, 'completed_at' => now()]);
        $course->refresh()->update(['status' => 'published']);

        $this->get('/lab')->assertViewHas('courses', fn ($courses) => $courses->contains('id', $course->id));
        $this->get(route('free.lesson', [$course, $active->slug]))->assertOk()->assertDontSee('old-stage');
        $this->get(route('free.lesson', [$course, $archived->slug]))->assertNotFound();
        app(ProgressService::class)->save($user, $active, [true, true, true]);
        $this->assertSame(100, app(ProgressService::class)->seriesScore($user, $course));
        $this->assertSame(100, $oldProgress->fresh()->progress_percent);
        $this->assertDatabaseCount('lesson_progress', 2);
    }

    public function test_five_website_lessons_award_twenty_points_each_and_require_final_task_acceptance(): void
    {
        $course = CourseSeries::create(LegacyCourseDefinitions::constitution()['build-a-website'] + [
            'slug' => 'build-a-website', 'title' => '10分钟搭建.com网站', 'minutes' => 10,
            'user_intent' => '拥有自己的网站', 'final_outcome' => '可用.com域名访问并可更新的网站', 'is_free' => true, 'price' => 0,
        ]);
        foreach (WebsiteSetupLessons::all() as $index => $definition) {
            unset($definition['role']);
            $course->lessons()->create($definition + ['position' => $index + 1, 'status' => 'published']);
        }
        $course->update(['status' => 'published']);
        $this->get(route('courses.show', $course))->assertRedirect(route('series.show', $course->slug));
        $introduction = $this->get('/series/build-a-website')->assertOk()->assertViewIs('frontend.series')
            ->assertSee('data-course-goal', false)->assertSee($course->final_outcome)
            ->assertSee('class="series-layout"', false)->assertSee('class="series-sidebar"', false)
            ->assertSee('免费学习')->assertSee('data-server-score="0"', false)
            ->assertDontSee('data-availability="purchase"', false);
        foreach ($course->lessons()->get() as $lesson) {
            $introduction->assertSee(route('free.lesson', [$course, $lesson->slug]), false)
                ->assertDontSee($lesson->prompt)->assertDontSee($lesson->content);
        }
        $introduction->assertViewHas('series', fn ($series) => count($series['lessons']) === 5
            && array_column($series['lessons'], 'score') === [20, 40, 60, 80, 100]);
        $this->get('/')->assertViewHas('series', fn ($courses) => collect($courses)->firstWhere('slug', $course->slug)['is_free'] === true);
        $this->actingAs(User::factory()->create());
        foreach ($course->lessons()->get() as $index => $lesson) {
            $this->get('/series/build-a-website/lessons/'.$lesson->slug)->assertRedirect(route('free.lesson', [$course, $lesson->slug]));
            $this->get(route('free.lesson', [$course, $lesson->slug]))->assertOk()->assertSee($lesson->title)
                ->assertViewHas('lessons', fn ($lessons) => $lessons->count() === 5);
            $this->postJson(route('free.lesson', [$course, $lesson->slug]).'/progress', ['checks' => array_fill(0, count($lesson->checks), true)])
                ->assertOk()->assertJson(['series_score' => ($index + 1) * 20]);
            $this->get('/series/build-a-website')->assertOk()
                ->assertSee('data-server-score="'.(($index + 1) * 20).'"', false)
                ->assertViewHas('completedLessonSlugs', $course->lessons()->get()->take($index + 1)->pluck('slug')->all());
        }
        $learner = LessonProgress::first()->user;
        $this->actingAs(User::factory()->create())->get('/series/build-a-website')
            ->assertOk()->assertSee('data-server-score="0"', false)->assertViewHas('completedLessonSlugs', []);
        $this->actingAs($learner);
        $last = $course->lessons()->reorder()->orderByDesc('position')->first();
        $this->postJson(route('free.lesson', [$course, $last->slug]).'/progress', ['checks' => [true, true, true, false]])
            ->assertOk()->assertJson(['completed' => false, 'series_score' => 80]);
        $this->postJson(route('free.lesson', [$course, $last->slug]).'/progress', ['checks' => [true, true, true, true]])
            ->assertOk()->assertJson(['completed' => true, 'series_score' => 100]);
        $this->assertDatabaseCount('lesson_progress', 5);
    }

    public function test_gradually_published_free_courses_are_excluded_until_the_task_is_complete(): void
    {
        $course = CourseSeries::first();
        $first = $course->lessons()->first();
        $first->update(['score' => 10, 'points' => 10]);
        $last = $first->replicate();
        $last->fill(['slug' => 'final-check', 'position' => 2, 'score' => 100, 'points' => 90, 'status' => 'draft'])->save();
        $course->refresh()->update(['status' => 'published']);

        $this->get('/lab')->assertViewHas('courses', fn ($courses) => ! $courses->contains('id', $course->id));
        $this->get('/lab?q=网页')->assertDontSee('Z：可以从「'.$course->title);
        $this->get(route('free.lesson', [$course, $first->slug]))->assertNotFound();
        $this->get(route('free.resource', [$course, $first->slug, $first->resources[0]['name']]))->assertNotFound();
        $this->actingAs(User::factory()->create())
            ->postJson(route('free.lesson', [$course, $first->slug]).'/progress', ['checks' => [true, true, true]])->assertNotFound();
        $this->assertDatabaseCount('lesson_progress', 0);

        $last->update(['status' => 'published']);
        $course->refresh()->update(['status' => 'published']);
        $this->get(route('free.lesson', [$course, $first->slug]))->assertOk();
        $this->get('/lab')->assertViewHas('courses', fn ($courses) => $courses->contains('id', $course->id));
    }

    public function test_free_lab_requires_full_weight_and_a_final_acceptance_lesson(): void
    {
        $course = CourseSeries::first();
        $lesson = $course->lessons()->first();

        foreach ([['points' => 10, 'score' => 100], ['points' => 100, 'score' => 10]] as $incomplete) {
            $lesson->update($incomplete);
            $course->refresh()->update(['status' => 'published']);
            $this->assertSame('published', $course->fresh()->status);
            $this->get('/lab')->assertViewHas('courses', fn ($courses) => ! $courses->contains('id', $course->id));
            $this->get(route('free.lesson', [$course, $lesson->slug]))->assertNotFound();
        }
    }

    public function test_future_theme_uses_same_content_and_login_returns_to_task_without_external_redirects(): void
    {
        config(['themes.active' => 'future']);
        $this->get('/lab')->assertOk()->assertSee('data-theme="future"', false);
        $path = '/lab/personal-intro-page/make-and-check';
        $this->get($path)->assertOk()->assertSee('data-page="lesson"', false)->assertSee('交给 Agent 的 Prompt');
        $this->get('/login?redirect='.urlencode($path))->assertOk()->assertSessionHas('url.intended', url($path));
        $this->get('/login?redirect=https://example.com')->assertSessionHas('url.intended', url($path));
        $user = User::factory()->create(['password' => 'Avalidpassword1']);
        $this->post('/login', ['email' => $user->email, 'password' => 'Avalidpassword1'])->assertRedirect(url($path));
    }
}
