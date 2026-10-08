<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicCourseRulesTest extends TestCase
{
    use RefreshDatabase;

    private function course(string $slug = 'dynamic-task', bool $free = true): CourseSeries
    {
        return CourseSeries::create([
            'slug' => $slug, 'title' => '做一个可验收的作品', 'category' => 'create', 'is_free' => $free,
            'user_intent' => '完成自己的作品', 'final_outcome' => '可打开且可修改的作品',
            'completion_criteria' => ['打开并核对作品'], 'agent_role' => ['生成作品'],
            'human_judgment_required' => ['核对实际结果'], 'recommendation_keywords' => ['动态课程'],
        ]);
    }

    private function lesson(CourseSeries $course, int $position): Lesson
    {
        return $course->lessons()->create([
            'slug' => 'step-'.$position, 'title' => '第'.$position.'步', 'position' => $position,
            'goal' => '完成一个可验证的步骤', 'prompt' => 'PRIVATE_PROMPT_'.$position,
            'steps' => [['title' => '做出结果', 'body' => '执行后由人核对成果']],
            'checks' => ['我已核对本步结果'], 'resources' => [['name' => 'sample.txt', 'label' => '示例资料', 'content' => 'PRIVATE_RESOURCE']],
            'points' => $position * 13, 'score' => $position, 'is_free' => false, 'status' => 'published',
        ]);
    }

    public function test_percent_recalculates_by_current_lesson_count_without_changing_acceptances(): void
    {
        $course = $this->course();
        $lessons = collect(range(1, 6))->map(fn ($n) => $this->lesson($course, $n));
        $course->refresh()->update(['status' => 'published']);
        $user = User::factory()->create();
        $service = app(ProgressService::class);
        $record = $service->save($user, $lessons[0]->fresh(), [true]);
        $before = $record->fresh()->getAttributes();
        $this->assertSame(16.7, $service->seriesPercent($user, $course));
        $extra = $this->lesson($course, 7);
        $course->refresh()->update(['status' => 'published']);
        $this->assertSame(14.3, $service->seriesPercent($user, $course));
        $extra->fresh()->update(['status' => 'archived']);
        $course->refresh()->update(['status' => 'published']);
        $this->assertSame(16.7, $service->seriesPercent($user, $course));
        $this->assertSame($before, $record->fresh()->getAttributes());
        foreach ($lessons as $lesson) { $service->save($user, $lesson->fresh(), [true]); }
        $this->assertSame(100, $service->seriesPercent($user, $course));
        $this->assertDatabaseCount('lesson_progress', 6);
        try {
            $lessons[0]->fresh()->update(['checks' => ['新的验收']]);
            $this->fail('Existing acceptances must remain protected.');
        } catch (ValidationException) {
            $this->assertSame(['我已核对本步结果'], $lessons[0]->fresh()->checks);
        }
    }

    public function test_free_course_overrides_every_lesson_flag_and_does_not_require_100_points(): void
    {
        $course = $this->course('free-six');
        $lessons = collect(range(1, 6))->map(fn ($n) => $this->lesson($course, $n));
        $course->refresh()->update(['status' => 'published']);
        $this->assertTrue(CourseSeries::freeLab()->whereKey($course->id)->exists());
        foreach ($lessons as $lesson) {
            $this->assertFalse($lesson->is_free);
            $this->get(route('lessons.show', [$course, $lesson->slug]))->assertOk()->assertSee($lesson->prompt)->assertSee('16.7%');
            $this->get(route('lessons.resource', [$course, $lesson->slug, 'sample.txt']))->assertOk()->assertSee('PRIVATE_RESOURCE');
        }
        $user = User::factory()->create();
        $this->actingAs($user)->postJson(route('lessons.progress', [$course, $lessons[0]->slug]), ['checks' => [true]])
            ->assertOk()->assertJson(['series_percent' => 16.7, 'completed' => true]);
        $this->get('/me')->assertOk()->assertSee('data-server-score="16.7"', false)->assertDontSee('本机试看累计');
    }

    public function test_paid_preview_and_download_permissions_use_current_database_settings(): void
    {
        $course = $this->course('paid-preview', false);
        $preview = $this->lesson($course, 1);
        $paid = $this->lesson($course, 2);
        $preview->fresh()->update(['is_free' => true]);
        $course->refresh()->update(['status' => 'published']);
        $this->get(route('lessons.show', [$course, $preview->slug]))->assertOk()->assertDontSee($paid->prompt);
        $this->get(route('lessons.show', [$course, $paid->slug]).'?is_free=1&subscription=active')->assertNotFound();
        $this->get(route('lessons.resource', [$course, $paid->slug, 'sample.txt']))->assertNotFound();
        $this->get(route('lessons.checklist', [$course, $paid->slug]))->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson(route('lessons.progress', [$course, $paid->slug]), ['checks' => [true]])->assertNotFound();
        $staleCourse = $course->fresh();
        $course->update(['is_free' => true]);
        $this->assertTrue(app(CourseAccessService::class)->canAccess($staleCourse, $paid));
        $staleCourse = $course->fresh();
        $course->update(['is_free' => false]);
        $this->assertFalse(app(CourseAccessService::class)->canAccess($staleCourse, $paid));
    }

    public function test_all_public_course_data_comes_from_database_including_new_slugs_and_order(): void
    {
        $this->get('/')->assertOk()->assertViewHas('series', []);
        $this->get('/series/build-a-website')->assertNotFound();
        $a = $this->course('new-admin-course');
        $this->lesson($a, 1);
        $a->refresh()->update(['status' => 'published', 'sort_order' => 20, 'title' => '后台设置的新课程']);
        $b = $this->course('another-new-course');
        $this->lesson($b, 1);
        $b->refresh()->update(['status' => 'published', 'sort_order' => 1]);
        foreach (['/', '/series'] as $path) {
            $this->get($path)->assertOk()->assertSee($a->title)->assertDontSee('PRIVATE_PROMPT')
                ->assertViewHas('series', fn ($items) => array_column($items, 'slug') === [$b->slug, $a->slug]);
        }
        $this->get('/series/'.$a->slug)->assertOk()->assertSee($a->title)->assertDontSee('PRIVATE_PROMPT');
        $a->update(['title' => 'PRIVATE_DRAFT_TITLE', 'status' => 'draft']);
        $this->get('/')->assertDontSee('PRIVATE_DRAFT_TITLE');
        $this->get('/series/'.$a->slug)->assertNotFound();
        $this->get(route('lessons.show', [$a, 'step-1']))->assertNotFound();
    }

    public function test_draft_steps_remain_in_denominator_until_archived_and_zero_lessons_is_zero_percent(): void
    {
        $course = $this->course();
        $user = User::factory()->create();
        $service = app(ProgressService::class);
        $this->assertSame(0, $service->seriesPercent($user, $course));
        $first = $this->lesson($course, 1);
        $draft = $this->lesson($course, 2);
        $draft->update(['status' => 'draft']);
        $course->refresh()->update(['status' => 'published']);
        $service->save($user, $first, [true]);
        $this->assertSame(50, $service->seriesPercent($user, $course));
        $this->get(route('lessons.show', [$course, $first->slug]))->assertOk()->assertSee('50%');
        $this->get(route('lessons.show', [$course, $draft->slug]))->assertNotFound();
        $draft->update(['status' => 'archived']);
        $course->refresh()->update(['status' => 'published']);
        $this->assertSame(100, $service->seriesPercent($user, $course));
    }

    public function test_stale_acceptance_form_cannot_confirm_revised_checks(): void
    {
        $course = $this->course();
        $lesson = $this->lesson($course, 1);
        $course->refresh()->update(['status' => 'published']);
        $lesson->fresh()->update(['checks' => ['核对新的成果条件']]);
        try {
            app(ProgressService::class)->save(User::factory()->create(), $lesson, [true]);
            $this->fail('A stale checklist must require a refresh.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('lesson_progress', 0);
        }
    }
}
