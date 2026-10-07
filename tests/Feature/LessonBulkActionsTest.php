<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\CourseDeletionService;
use App\Services\LessonBulkActionService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LessonBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private CourseSeries $course;
    private Lesson $first;
    private Lesson $second;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
        $this->course = CourseSeries::create([
            'slug' => 'bulk-task', 'title' => '做一个可打开的网页', 'category' => 'create', 'is_free' => true, 'price' => 0,
            'user_intent' => '展示信息', 'final_outcome' => '可打开的网页', 'completion_criteria' => ['打开并核对'],
            'agent_role' => ['制作网页'], 'human_judgment_required' => ['核对内容'],
        ]);
        $this->first = $this->course->lessons()->create([
            'slug' => 'first', 'title' => '生成网页', 'goal' => '打开网页', 'steps' => [['title' => '生成', 'body' => '生成网页并打开']],
            'prompt' => '生成网页', 'checks' => ['可以打开'], 'position' => 1, 'score' => 50, 'points' => 50,
        ]);
        $this->second = $this->first->replicate();
        $this->second->fill(['slug' => 'second', 'title' => '核对网页', 'position' => 2, 'score' => 100])->save();
    }

    private function publishCourse(): void
    {
        app(LessonBulkActionService::class)->publish($this->admin, [$this->first->id, $this->second->id]);
        $this->course->refresh()->update(['status' => 'published']);
    }

    public function test_admin_can_select_and_publish_lessons_without_automatically_publishing_the_course(): void
    {
        Livewire::test(ListLessons::class)->assertTableBulkActionVisible('publish')->assertTableBulkActionVisible('delete')
            ->assertTableBulkActionHidden('restore')
            ->callTableBulkAction('publish', [$this->first, $this->second])->assertHasNoTableBulkActionErrors();
        $this->assertSame('published', $this->first->fresh()->status);
        $this->assertSame('published', $this->second->fresh()->status);
        $this->assertSame('draft', $this->course->fresh()->status);
        $this->get(route('free.lesson', [$this->course, $this->first->slug]))->assertNotFound();
    }

    public function test_invalid_publication_rolls_back_the_whole_batch_and_keeps_selection(): void
    {
        $unselected = $this->first->replicate();
        $unselected->fill(['slug' => 'unselected', 'status' => 'published'])->save();
        $this->course->refresh()->update(['status' => 'published']);
        $this->second->fresh()->update(['prompt' => '']);
        Livewire::test(ListLessons::class)->callTableBulkAction('publish', [$this->first, $this->second])
            ->assertNotified('批量操作未完成')->assertSet('selectedTableRecords', [(string) $this->first->id, (string) $this->second->id]);
        $this->assertSame('published', $this->course->fresh()->status);
        $this->assertSame('draft', $this->first->fresh()->status);
        $this->assertSame('draft', $this->second->fresh()->status);
    }

    public function test_deletion_is_recoverable_and_restoration_requires_review(): void
    {
        $this->publishCourse();
        $this->first->update(['video_poster' => 'lesson-video-posters/retained.png']);
        Livewire::test(ListLessons::class)->callTableBulkAction('delete', [$this->first])->assertHasNoTableBulkActionErrors();
        $this->assertSoftDeleted($this->first);
        $this->assertSame('draft', $this->course->fresh()->status);
        $this->assertDatabaseHas('lessons', ['id' => $this->first->id, 'video_poster' => 'lesson-video-posters/retained.png', 'prompt' => '生成网页']);
        Livewire::test(ListLessons::class)->assertCanNotSeeTableRecords([$this->first])->assertCanSeeTableRecords([$this->second]);
        $this->get('/galaxy/lessons/'.$this->first->id.'/edit')->assertNotFound();
        $this->get(route('free.lesson', [$this->course, $this->first->slug]))->assertNotFound();
        $this->assertFalse(app(CourseAccessService::class)->canAccess($this->course->fresh(), $this->first));
        Livewire::test(ListLessons::class)->set('activeTab', 'trash')->assertCanSeeTableRecords([$this->first])
            ->assertTableBulkActionHidden('publish')->assertTableBulkActionHidden('delete')->assertTableBulkActionVisible('restore')
            ->callTableBulkAction('restore', [$this->first])->assertHasNoTableBulkActionErrors();
        $this->assertNotSoftDeleted($this->first);
        $this->assertSame('draft', $this->first->fresh()->status);
        $this->assertSame('draft', $this->course->fresh()->status);
        $this->assertFalse(Gate::allows('forceDelete', $this->first));
    }

    public function test_any_learning_record_blocks_the_entire_delete_batch(): void
    {
        $this->publishCourse();
        $progress = LessonProgress::create(['lesson_id' => $this->second->id, 'user_id' => User::factory()->create()->id,
            'checks' => [false], 'progress_percent' => 0]);
        Livewire::test(ListLessons::class)->callTableBulkAction('delete', [$this->first, $this->second])->assertNotified('批量操作未完成');
        $this->assertNotSoftDeleted($this->first);
        $this->assertNotSoftDeleted($this->second);
        $this->assertSame('published', $this->course->fresh()->status);
        $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id, 'progress_percent' => 0]);
        $this->assertFalse(Gate::allows('delete', $this->second));
    }

    public function test_non_admins_and_revoked_admins_cannot_mutate_selected_lessons(): void
    {
        foreach ([User::factory()->create(), $this->admin] as $actor) {
            if ($actor->id === $this->admin->id) {
                User::whereKey($actor->id)->update(['is_admin' => false]);
            }
            foreach (['publish', 'trash', 'restore'] as $operation) {
                try {
                    app(LessonBulkActionService::class)->$operation($actor, [$this->first->id]);
                    $this->fail('Non-admin bulk action must be denied.');
                } catch (AuthorizationException) {
                    $this->assertSame('draft', $this->first->fresh()->status);
                    $this->assertNotSoftDeleted($this->first);
                }
            }
        }
    }

    public function test_missing_or_stale_selection_never_partially_changes_records(): void
    {
        $service = app(LessonBulkActionService::class);
        foreach ([[], [$this->first->id, $this->first->id], [$this->first->id, 999999]] as $ids) {
            try {
                $service->publish($this->admin, $ids);
                $this->fail('Invalid selection must fail.');
            } catch (ValidationException) {
                $this->assertSame('draft', $this->first->fresh()->status);
            }
        }
        $service->trash($this->admin, [$this->second->id]);
        try {
            $service->publish($this->admin, [$this->first->id, $this->second->id]);
            $this->fail('Deleted lessons cannot be published.');
        } catch (ValidationException) {
            $this->assertSame('draft', $this->first->fresh()->status);
            $this->assertSoftDeleted($this->second);
        }
    }

    public function test_course_final_deletion_includes_lessons_in_the_lesson_recycle_bin(): void
    {
        $service = app(LessonBulkActionService::class);
        $service->trash($this->admin, [$this->first->id]);
        app(CourseDeletionService::class)->trash($this->admin, $this->course);
        app(CourseDeletionService::class)->permanentlyDelete($this->admin, $this->course->fresh(), $this->course->slug);
        $this->assertDatabaseMissing('lessons', ['id' => $this->first->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $this->second->id]);
        $this->assertDatabaseMissing('course_series', ['id' => $this->course->id]);
    }

    public function test_course_final_deletion_cannot_ignore_progress_on_a_trashed_lesson(): void
    {
        app(LessonBulkActionService::class)->trash($this->admin, [$this->first->id]);
        // Simulate historical or out-of-band data; final deletion must still inspect all lessons.
        $progress = LessonProgress::create(['lesson_id' => $this->first->id, 'user_id' => User::factory()->create()->id,
            'checks' => [false], 'progress_percent' => 0]);
        app(CourseDeletionService::class)->trash($this->admin, $this->course);
        try {
            app(CourseDeletionService::class)->permanentlyDelete($this->admin, $this->course->fresh(), $this->course->slug);
            $this->fail('Historical progress must block final deletion.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id]);
            $this->assertDatabaseHas('lessons', ['id' => $this->first->id]);
            $this->assertSoftDeleted($this->course);
        }
    }

    public function test_archived_lessons_remain_visible_in_recycle_bin_and_parent_trash_blocks_restore(): void
    {
        $this->first->update(['status' => 'archived']);
        app(LessonBulkActionService::class)->trash($this->admin, [$this->first->id]);
        Livewire::test(ListLessons::class)->set('activeTab', 'trash')->assertCanSeeTableRecords([$this->first]);
        app(CourseDeletionService::class)->trash($this->admin, $this->course);
        try {
            app(LessonBulkActionService::class)->restore($this->admin, [$this->first->id]);
            $this->fail('Restore parent first.');
        } catch (ValidationException) {
            $this->assertSoftDeleted($this->first);
        }
    }

    public function test_legacy_preview_and_download_cannot_resurface_a_deleted_lesson(): void
    {
        $this->course->update(['slug' => 'build-a-website']);
        $this->first->update(['slug' => 'server-and-ip']);
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertOk();
        app(LessonBulkActionService::class)->trash($this->admin, [$this->first->id]);
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertNotFound();
        $this->get('/series/build-a-website/lessons/server-and-ip/checklist')->assertNotFound();
        $this->get('/series/build-a-website')->assertOk()->assertViewHas('series', fn ($series) => ! collect($series['lessons'])->contains('slug', 'server-and-ip'));
    }

    public function test_stale_ui_selection_is_not_silently_reduced_to_a_partial_batch(): void
    {
        app(LessonBulkActionService::class)->trash($this->admin, [$this->second->id]);
        Livewire::test(ListLessons::class)->callTableBulkAction('publish', [$this->first, $this->second])->assertNotified('批量操作未完成');
        $this->assertSame('draft', $this->first->fresh()->status);
        $this->assertSoftDeleted($this->second);
    }
}
