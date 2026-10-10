<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseDeletionService;
use App\Services\FreeLabInstaller;
use App\Services\LessonNoteService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LessonNotesTest extends TestCase
{
    use RefreshDatabase;

    private CourseSeries $course;

    private Lesson $lesson;

    private User $learner;

    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        $this->course = CourseSeries::firstOrFail();
        $this->lesson = $this->course->lessons()->firstOrFail();
        $this->learner = User::factory()->create();
        $this->path = route('lessons.notes', [$this->course, $this->lesson->slug]);
    }

    public function test_guest_requires_login_and_saving_notes_cannot_grant_progress_or_spoof_an_owner(): void
    {
        $this->get(route('free.lesson', [$this->course, $this->lesson->slug]))->assertOk()->assertSee('登录写笔记')
            ->assertDontSee('data-lesson-notes', false)->assertHeader('Cache-Control', 'no-store, private');
        $this->putJson($this->path, ['notes' => 'guest', 'notes_version' => 0])->assertUnauthorized();
        $other = User::factory()->create();
        $this->actingAs($this->learner)->putJson($this->path, [
            'notes' => "  记录一次实验\n保留空格与换行  ", 'notes_version' => 0,
            'user_id' => $other->id, 'lesson_id' => 999, 'progress_percent' => 100,
            'completed_at' => now(), 'checks' => [true, true, true], 'deleted_course_snapshot' => ['course_id' => 99],
        ])->assertOk()->assertJson(['notes_version' => 1])->assertHeader('Cache-Control', 'no-store, private')->assertJsonMissingPath('notes');
        $record = LessonProgress::sole();
        $this->assertSame($this->learner->id, $record->user_id);
        $this->assertSame($this->lesson->id, $record->lesson_id);
        $this->assertSame("  记录一次实验\n保留空格与换行  ", $record->notes);
        $this->assertSame([], $record->checks);
        $this->assertSame(0, $record->progress_percent);
        $this->assertNull($record->completed_at);
        $this->assertNull($record->deleted_course_snapshot);
        $this->assertSame(0, app(ProgressService::class)->seriesPercent($this->learner, $this->course));
    }

    public function test_notes_are_shared_by_both_learning_urls_but_private_to_account_and_lesson_and_escaped(): void
    {
        $body = 'PERSONAL_NOTE_<script>alert("private")</script>';
        $this->actingAs($this->learner)->putJson($this->path, ['notes' => $body, 'notes_version' => 0])->assertOk();
        foreach (['free.lesson', 'lessons.show'] as $name) {
            $this->get(route($name, [$this->course->slug, $this->lesson->slug]))->assertOk()->assertSee($body)
                ->assertDontSee($body, false)->assertHeader('Cache-Control', 'no-store, private');
        }
        $otherCourse = CourseSeries::whereKeyNot($this->course->id)->firstOrFail();
        $this->get(route('free.lesson', [$otherCourse, $otherCourse->lessons()->first()->slug]))->assertDontSee('PERSONAL_NOTE');
        $this->actingAs(User::factory()->create())->get(route('lessons.show', [$this->course, $this->lesson->slug]))->assertDontSee('PERSONAL_NOTE');
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('lessons.show', [$this->course, $this->lesson->slug]))->assertDontSee('PERSONAL_NOTE');
        $this->get(route('courses.preview', [$this->course, $this->lesson->slug]))->assertOk()->assertDontSee('PERSONAL_NOTE')
            ->assertDontSee('data-lesson-notes', false)->assertSee('前台预览不读取或保存课堂笔记');
    }

    public function test_note_edits_and_progress_saves_preserve_each_others_fields(): void
    {
        $this->actingAs($this->learner)->putJson($this->path, ['notes' => '第一份笔记', 'notes_version' => 0])->assertOk();
        $record = app(ProgressService::class)->save($this->learner, $this->lesson, array_fill(0, count($this->lesson->checks), true));
        $record->last_position_seconds = 45;
        $record->save();
        $before = $record->fresh()->getAttributes();
        $this->putJson($this->path, ['notes' => '补充验收证据', 'notes_version' => 1])->assertOk()->assertJson(['notes_version' => 2]);
        $after = $record->fresh();
        foreach (['id', 'user_id', 'lesson_id', 'checks', 'progress_percent', 'last_position_seconds', 'completed_at', 'created_at', 'deleted_course_snapshot'] as $field) {
            $this->assertSame($before[$field], $after->getAttributes()[$field]);
        }
        $this->postJson(route('lessons.progress', [$this->course, $this->lesson->slug]), [
            'checks' => array_fill(0, count($this->lesson->checks), true), 'notes' => '不能覆盖', 'notes_version' => 999,
        ])->assertOk();
        $this->assertSame('补充验收证据', $record->fresh()->notes);
        $this->assertSame(2, $record->fresh()->notes_version);
        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_conflict_retry_and_clearing_are_safe(): void
    {
        $this->actingAs($this->learner)->putJson($this->path, ['notes' => '', 'notes_version' => 4])->assertConflict();
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->putJson($this->path, ['notes' => '已保存', 'notes_version' => 0])->assertOk()->assertJson(['notes_version' => 1]);
        $before = LessonProgress::sole()->getAttributes();
        $this->putJson($this->path, ['notes' => '已保存', 'notes_version' => 0])->assertOk()->assertJson(['notes_version' => 1]);
        $this->assertSame($before, LessonProgress::sole()->getAttributes());
        $this->putJson($this->path, ['notes' => '另一页的旧修改', 'notes_version' => 0])->assertConflict();
        $this->assertSame($before, LessonProgress::sole()->getAttributes());
        $this->putJson($this->path, ['notes' => '', 'notes_version' => 1])->assertOk()->assertJson(['notes_version' => 2]);
        $this->assertNull(LessonProgress::sole()->notes);
        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_invalid_input_and_non_javascript_conflict_keep_saved_notes_and_flash_draft(): void
    {
        $this->actingAs($this->learner)->putJson($this->path, ['notes' => str_repeat('字', 10001), 'notes_version' => 0])->assertUnprocessable();
        $this->putJson($this->path, ['notes' => ['bad'], 'notes_version' => 0])->assertUnprocessable();
        $this->putJson($this->path, ['notes' => 'bad', 'notes_version' => -1])->assertUnprocessable();
        $this->putJson($this->path, ['notes' => 'bad'])->assertUnprocessable();
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->putJson($this->path, ['notes' => str_repeat('字', 10000), 'notes_version' => 0])->assertOk();
        $from = route('lessons.show', [$this->course, $this->lesson->slug]);
        $this->from($from)->put($this->path, ['notes' => '未保存的草稿', 'notes_version' => 0])
            ->assertRedirect($from)->assertSessionHasErrors('notes_version')->assertSessionHas('_old_input.notes', '未保存的草稿');
        $this->get($from)->assertSee('未保存的草稿')->assertSee('data-note-dirty="true"', false);
        $this->assertSame(str_repeat('字', 10000), LessonProgress::sole()->notes);
        $migration = require database_path('migrations/2026_10_10_160000_add_notes_to_lesson_progress.php');
        try {
            $migration->down();
            $this->fail('A rollback must not discard classroom notes.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Classroom notes exist', $exception->getMessage());
        }
        $this->assertSame(str_repeat('字', 10000), LessonProgress::sole()->notes);
    }

    public function test_access_is_rechecked_and_owner_flags_cannot_unlock_paid_draft_or_deleted_content(): void
    {
        $this->actingAs($this->learner);
        $other = CourseSeries::whereKeyNot($this->course->id)->firstOrFail();
        $this->putJson(route('lessons.notes', [$other, $this->lesson->slug]), ['notes' => 'bad', 'notes_version' => 0])->assertNotFound();
        $this->course->update(['is_free' => false]);
        $this->putJson($this->path, ['notes' => 'bad', 'notes_version' => 0, 'purchased' => true, 'subscription' => 'active'])->assertNotFound();
        $this->lesson->update(['is_free' => true]);
        $this->putJson($this->path, ['notes' => '试看笔记', 'notes_version' => 0])->assertOk();
        $this->course->update(['status' => 'draft']);
        $this->putJson($this->path, ['notes' => 'bad', 'notes_version' => 1])->assertNotFound();
        $this->course->update(['status' => 'published']);
        $this->lesson->update(['status' => 'archived']);
        $this->putJson($this->path, ['notes' => 'bad', 'notes_version' => 1])->assertNotFound();
        $this->lesson->update(['status' => 'published']);
        $this->course->update(['status' => 'published']);
        $admin = User::factory()->create(['is_admin' => true]);
        app(CourseDeletionService::class)->trash($admin, $this->course);
        $this->putJson($this->path, ['notes' => 'bad', 'notes_version' => 1])->assertNotFound();
        try {
            app(LessonNoteService::class)->save($this->learner, $this->lesson, 'stale model', 1);
            $this->fail('A stale model must not bypass access.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame('试看笔记', LessonProgress::sole()->notes);
    }

    public function test_notes_survive_course_final_deletion_and_history_is_only_visible_to_owner(): void
    {
        $this->actingAs($this->learner)->putJson($this->path, ['notes' => "HISTORY_PERSONAL_NOTE\n下一步", 'notes_version' => 0])->assertOk();
        $record = LessonProgress::sole();
        $admin = User::factory()->create(['is_admin' => true]);
        $deletion = app(CourseDeletionService::class);
        $deletion->trash($admin, $this->course);
        $deletion->permanentlyDelete($admin, $this->course->fresh());
        $this->assertNull($record->fresh()->lesson_id);
        $this->assertSame("HISTORY_PERSONAL_NOTE\n下一步", $record->fresh()->notes);
        $this->get(route('learning.history', $record))->assertStatus(410)->assertSee('HISTORY_PERSONAL_NOTE')->assertSee('你的课堂笔记')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($admin)->get(route('learning.history', $record))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('learning.history', $record))->assertNotFound();
        $this->putJson($this->path, ['notes' => 'overwrite', 'notes_version' => 1])->assertNotFound();
    }

    public function test_note_save_rate_limit_and_csrf_protection_remain_enabled(): void
    {
        $this->actingAs($this->learner);
        for ($i = 0; $i < 30; $i++) {
            $this->putJson($this->path, ['notes' => '相同笔记', 'notes_version' => 0])->assertOk();
        }
        $this->putJson($this->path, ['notes' => '多一次', 'notes_version' => 1])->assertTooManyRequests();
        $this->assertSame('相同笔记', LessonProgress::sole()->notes);
        $this->app['env'] = 'production'; // Database remains the configured isolated SQLite test connection.
        $this->putJson($this->path, ['notes' => '无CSRF', 'notes_version' => 1])->assertStatus(419);
    }
}
