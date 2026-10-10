<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseDeletionService;
use App\Services\CourseRelationService;
use App\Services\FreeLabInstaller;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CourseBulkDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CourseSeries $first;

    private CourseSeries $second;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
        app(FreeLabInstaller::class)->install();
        [$this->first, $this->second] = CourseSeries::orderBy('id')->take(2)->get()->all();
    }

    private function trashCourses(): void
    {
        foreach ([$this->first, $this->second] as $course) {
            app(CourseDeletionService::class)->trash($this->admin, $course);
            $course->refresh();
        }
    }

    public function test_selection_only_appears_in_trash_and_cancel_or_tab_switch_does_not_delete(): void
    {
        $this->trashCourses();
        $table = Livewire::test(ListCourseSeries::class)->assertTableBulkActionHidden('forceDelete');
        $this->assertFalse($table->instance()->getTable()->isSelectionEnabled());
        $table->set('activeTab', 'trash')->assertTableBulkActionVisible('forceDelete');
        $this->assertTrue($table->instance()->getTable()->isSelectionEnabled());
        $this->assertTrue($table->instance()->getTable()->selectsCurrentPageOnly());
        $table->mountTableBulkAction('forceDelete', [$this->first, $this->second])->assertDontSee('输入所选课程地址标识');
        $action = $table->instance()->getTable()->getBulkAction('forceDelete');
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('确认', $action->getModalSubmitActionLabel());
        $table->unmountAction()->set('activeTab', 'courses')->assertSet('selectedTableRecords', []);
        $this->assertSoftDeleted($this->first);
        $this->assertSoftDeleted($this->second);
    }

    public function test_batch_removes_only_selected_courses_and_all_their_lessons_and_relations_but_keeps_files(): void
    {
        Storage::fake('public');
        $this->first->update(['cover' => 'course-covers/retained.jpg']);
        Storage::disk('public')->put($this->first->cover, 'retained cover');
        $other = CourseSeries::whereKeyNot($this->first->id)->whereKeyNot($this->second->id)->first();
        app(CourseRelationService::class)->save($this->admin, $other, [
            'related_course_series_id' => $this->first->id, 'relation_type' => 'next', 'sort_order' => 1,
        ]);
        $this->first->lessons()->first()->delete();
        $this->trashCourses();
        Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->callTableBulkAction('forceDelete', [$this->first, $this->second])
            ->assertHasNoTableBulkActionErrors()->assertNotified('所选课程已最终删除')->assertSet('selectedTableRecords', []);
        foreach ([$this->first, $this->second] as $course) {
            $this->assertDatabaseMissing('course_series', ['id' => $course->id]);
            $this->assertDatabaseMissing('lessons', ['course_series_id' => $course->id]);
            $this->assertDatabaseHas('course_catalog_suppressions', ['slug' => $course->slug]);
        }
        $this->assertDatabaseCount('course_relations', 0);
        $this->assertDatabaseHas('course_series', ['id' => $other->id, 'deleted_at' => null]);
        $this->assertDatabaseCount('lessons', 1);
        Storage::disk('public')->assertExists('course-covers/retained.jpg');
        $this->assertSame(0, app(FreeLabInstaller::class)->install());
    }

    public function test_progress_on_a_trashed_lesson_is_preserved_when_the_entire_course_batch_is_deleted(): void
    {
        $lesson = $this->second->lessons()->first();
        $lesson->delete();
        $progress = LessonProgress::create(['user_id' => User::factory()->create()->id, 'lesson_id' => $lesson->id,
            'checks' => [false], 'progress_percent' => 0]);
        $edge = app(CourseRelationService::class)->save($this->admin, $this->first, [
            'related_course_series_id' => $this->second->id, 'relation_type' => 'next', 'sort_order' => 1,
        ]);
        $this->trashCourses();
        Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->callTableBulkAction('forceDelete', [$this->first, $this->second])->assertNotified('所选课程已最终删除')
            ->assertSet('selectedTableRecords', []);
        foreach ([$this->first, $this->second] as $course) {
            $this->assertDatabaseMissing('course_series', ['id' => $course->id]);
            $this->assertDatabaseMissing('lessons', ['course_series_id' => $course->id]);
            $this->assertDatabaseHas('course_catalog_suppressions', ['slug' => $course->slug]);
        }
        $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id, 'lesson_id' => null, 'progress_percent' => 0]);
        $this->assertSame($lesson->title, $progress->fresh()->deleted_course_snapshot['lesson_title']);
        $this->assertDatabaseMissing('course_relations', ['id' => $edge->id]);
    }

    public function test_failure_on_a_later_course_rolls_back_deleted_content_and_detached_history(): void
    {
        $lesson = $this->first->lessons()->first();
        $progress = LessonProgress::create(['user_id' => $this->admin->id, 'lesson_id' => $lesson->id, 'checks' => [false], 'progress_percent' => 0]);
        $this->trashCourses();
        $this->partialMock(CourseRelationService::class, function ($mock) {
            $mock->shouldReceive('removeForDeletedCourse')->andReturnUsing(function (User $user, CourseSeries $course) {
                if ($course->id === $this->second->id) {
                    throw ValidationException::withMessages(['confirmation' => '模拟关联清理失败']);
                }
            });
        });
        Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->callTableBulkAction('forceDelete', [$this->first, $this->second])->assertNotified('批量删除未完成');
        foreach ([$this->first, $this->second] as $course) {
            $this->assertSoftDeleted($course);
            $this->assertDatabaseHas('lessons', ['course_series_id' => $course->id]);
            $this->assertDatabaseMissing('course_catalog_suppressions', ['slug' => $course->slug]);
        }
        $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id, 'lesson_id' => $lesson->id, 'deleted_course_snapshot' => null]);
    }

    public function test_invalid_missing_and_restored_selections_never_partially_delete(): void
    {
        $this->trashCourses();
        $service = app(CourseDeletionService::class);
        foreach ([[], [$this->first->id, $this->first->id], [$this->first->id, 'invalid'], [$this->first->id, 999999]] as $ids) {
            try {
                $service->permanentlyDeleteMany($this->admin, $ids);
                $this->fail('Invalid selection must fail.');
            } catch (ValidationException) {
                $this->assertSoftDeleted($this->first);
                $this->assertDatabaseMissing('course_catalog_suppressions', ['slug' => $this->first->slug]);
            }
        }
        $service->restore($this->admin, $this->second);
        try {
            $service->permanentlyDeleteMany($this->admin, [$this->first->id, $this->second->id]);
            $this->fail('Restored courses must block the batch.');
        } catch (ValidationException) {
            $this->assertSoftDeleted($this->first);
            $this->assertNotSoftDeleted($this->second);
        }
    }

    public function test_stale_or_filtered_selection_after_opening_modal_is_rejected(): void
    {
        $this->trashCourses();
        $table = Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->mountTableBulkAction('forceDelete', [$this->first, $this->second]);
        app(CourseDeletionService::class)->restore($this->admin, $this->second);
        $table->callMountedTableBulkAction()->assertNotified('批量删除未完成');
        $this->assertSoftDeleted($this->first);
        $this->assertNotSoftDeleted($this->second);
        $this->assertDatabaseCount('course_catalog_suppressions', 0);
    }

    public function test_non_admin_and_revoked_admin_cannot_delete_even_with_a_previously_open_modal(): void
    {
        $this->trashCourses();
        $table = Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->mountTableBulkAction('forceDelete', [$this->first]);
        $ordinary = User::factory()->create();
        $this->actingAs($ordinary);
        $table->callMountedTableBulkAction()->assertForbidden();
        foreach ([$ordinary, $this->admin] as $actor) {
            if ($actor->id === $this->admin->id) {
                User::whereKey($actor->id)->update(['is_admin' => false]);
            }
            try {
                app(CourseDeletionService::class)->permanentlyDeleteMany($actor, [$this->first->id]);
                $this->fail('Only current administrators may delete.');
            } catch (AuthorizationException) {
                $this->assertSoftDeleted($this->first);
            }
        }
    }
}
