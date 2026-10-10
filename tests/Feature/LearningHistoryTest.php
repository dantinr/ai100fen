<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseDeletionService;
use App\Services\FreeLabInstaller;
use App\Services\ProgressService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LearningHistoryTest extends TestCase
{
    use RefreshDatabase;

    private User $learner;

    private User $admin;

    private CourseSeries $course;

    private LessonProgress $progress;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->learner = User::factory()->create();
        $this->course = CourseSeries::firstOrFail();
        $this->progress = app(ProgressService::class)->save($this->learner, $this->course->lessons()->first(), [true, false, false]);
    }

    private function deletePermanently(): void
    {
        $service = app(CourseDeletionService::class);
        $service->trash($this->admin, $this->course);
        $service->permanentlyDelete($this->admin, $this->course->fresh());
    }

    public function test_soft_deletion_keeps_history_visible_and_clicking_shows_a_private_unavailable_notice(): void
    {
        $before = $this->progress->fresh()->getAttributes();
        app(CourseDeletionService::class)->trash($this->admin, $this->course);
        $this->actingAs($this->learner)->get('/me')->assertOk()->assertSee($this->course->title)->assertSee('课程已下架')
            ->assertSee(route('learning.history', $this->progress->id))->assertDontSee('data-server-score', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('learning.history', $this->progress->id))->assertStatus(410)->assertSee('课程已下架')
            ->assertSee('验收进度 33%')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame($before, $this->progress->fresh()->getAttributes());
        $this->get(route('series.show', $this->course))->assertNotFound();
    }

    public function test_final_deletion_preserves_every_learning_field_and_does_not_expose_deleted_content(): void
    {
        $lesson = $this->course->lessons()->first();
        $lesson->update(['prompt' => 'PRIVATE_PROMPT_CONTENT', 'content' => 'PRIVATE_LESSON_CONTENT']);
        $before = $this->progress->fresh()->getAttributes();
        $this->deletePermanently();
        $this->assertDatabaseMissing('course_series', ['id' => $this->course->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $after = $this->progress->fresh();
        $this->assertNull($after->lesson_id);
        foreach (['id', 'user_id', 'checks', 'progress_percent', 'last_position_seconds', 'completed_at', 'created_at', 'updated_at'] as $field) {
            $this->assertSame($before[$field], $after->getAttributes()[$field]);
        }
        $this->assertSame($lesson->title, $after->deleted_course_snapshot['lesson_title']);
        $this->actingAs($this->learner)->get('/me')->assertOk()->assertSee($this->course->title)->assertSee('课程已下架');
        foreach (['pop', 'future'] as $theme) {
            config(['themes.active' => $theme]);
            $this->get(route('learning.history', $after->id))->assertStatus(410)->assertSee('课程已下架')
                ->assertSee($lesson->title)->assertDontSee('PRIVATE_PROMPT_CONTENT')->assertDontSee('PRIVATE_LESSON_CONTENT');
        }
        $this->postJson(route('lessons.progress', [$this->course->slug, $lesson->slug]), ['checks' => [true, true, true]])->assertNotFound();
    }

    public function test_history_is_only_available_to_its_owner_including_when_an_administrator_requests_it(): void
    {
        $this->deletePermanently();
        $url = route('learning.history', $this->progress->id);
        $this->get($url)->assertRedirect('/login');
        foreach ([User::factory()->create(), $this->admin] as $other) {
            $this->actingAs($other)->get($url)->assertNotFound()->assertDontSee($this->course->title);
            $this->get('/me')->assertOk()->assertDontSee($this->course->title);
        }
        $this->actingAs($this->learner)->get($url)->assertStatus(410);
    }

    public function test_multiple_records_and_multiple_learners_survive_deletion_without_being_merged_or_exposed(): void
    {
        $lesson = $this->course->lessons()->first();
        $second = $lesson->replicate();
        $second->slug = 'another-real-step';
        $second->save();
        $this->course->refresh()->update(['status' => 'published']);
        $completed = app(ProgressService::class)->save($this->learner, $second, [true, true, true]);
        $otherUser = User::factory()->create();
        $otherProgress = app(ProgressService::class)->save($otherUser, $lesson, [false, false, false]);
        $this->deletePermanently();
        $this->assertDatabaseCount('lesson_progress', 3);
        $this->assertSame(3, LessonProgress::whereNull('lesson_id')->count());
        $this->assertSame(100, $completed->fresh()->progress_percent);
        $this->assertNotNull($completed->fresh()->completed_at);
        $this->actingAs($this->learner)->get('/me')->assertOk()
            ->assertViewHas('courseProgress', fn ($courses) => $courses->count() === 1)
            ->assertViewHas('freeProgress', fn ($records) => $records->count() === 2 && ! $records->contains('id', $otherProgress->id));
        $this->actingAs($otherUser)->get('/me')->assertOk()
            ->assertViewHas('freeProgress', fn ($records) => $records->count() === 1 && $records->contains('id', $otherProgress->id));
    }

    public function test_restoring_and_republishing_reconnects_the_original_history(): void
    {
        $service = app(CourseDeletionService::class);
        $url = route('learning.history', $this->progress->id);
        $lesson = $this->course->lessons()->first();
        $this->actingAs($this->learner)->get($url)->assertRedirect(route('lessons.show', [$this->course->slug, $lesson->slug]));
        $service->trash($this->admin, $this->course);
        $service->restore($this->admin, $this->course->fresh());
        $this->get($url)->assertForbidden()->assertSee('课程暂未开放');
        $this->course->fresh()->update(['status' => 'published']);
        $this->get($url)->assertRedirect(route('lessons.show', [$this->course->slug, $lesson->slug]));
        $this->assertSame(33, $this->progress->fresh()->progress_percent);
    }

    public function test_a_new_course_with_the_same_slug_does_not_capture_old_history(): void
    {
        $oldLesson = $this->course->lessons()->first();
        $newCourse = $this->course->replicate();
        $newLesson = $oldLesson->replicate();
        $this->deletePermanently();
        $newCourse->status = 'draft';
        $newCourse->save();
        $newLesson->course_series_id = $newCourse->id;
        $newLesson->save();
        $newCourse->update(['status' => 'published']);
        $this->actingAs($this->learner)->get(route('learning.history', $this->progress->id))->assertStatus(410)->assertSee('课程已下架');
        $this->assertSame($this->course->id, $this->progress->fresh()->deleted_course_snapshot['course_id']);
        $this->assertNull($this->progress->fresh()->lesson_id);
    }

    public function test_history_cannot_bypass_access_rules_when_a_course_is_no_longer_free(): void
    {
        $this->course->update(['is_free' => false]);
        $this->course->lessons()->first()->update(['is_free' => false, 'prompt' => 'PAID_PRIVATE_PROMPT']);
        $this->actingAs($this->learner)->get(route('learning.history', $this->progress->id))->assertForbidden()
            ->assertSee('课程暂未开放')->assertDontSee('PAID_PRIVATE_PROMPT');
        $this->get('/me')->assertOk()->assertSee('验收进度 33%');
    }

    public function test_direct_database_deletion_is_still_blocked_and_migration_rollback_cannot_discard_detached_history(): void
    {
        try {
            DB::table('lessons')->where('id', $this->progress->lesson_id)->delete();
            $this->fail('The original restrictive foreign key must remain active.');
        } catch (QueryException) {
            $this->assertDatabaseHas('lessons', ['id' => $this->progress->lesson_id]);
        }
        $this->deletePermanently();
        $migration = require database_path('migrations/2026_10_10_140000_preserve_progress_after_course_deletion.php');
        try {
            $migration->down();
            $this->fail('Rollback must not discard retained learning history.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Archived learning records exist', $exception->getMessage());
            $this->assertNotNull($this->progress->fresh()->deleted_course_snapshot);
        }
    }
}
