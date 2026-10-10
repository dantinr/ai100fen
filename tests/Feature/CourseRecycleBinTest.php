<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\CourseDeletionService;
use App\Services\CourseRelationService;
use App\Services\FreeLabInstaller;
use App\Services\LegacyCourseImporter;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CourseRecycleBinTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
        app(FreeLabInstaller::class)->install();
    }

    public function test_deletion_preserves_content_progress_and_relations_and_restore_requires_republication(): void
    {
        $course = CourseSeries::first();
        $lesson = $course->lessons()->first();
        $learner = User::factory()->create();
        $progress = LessonProgress::create(['user_id' => $learner->id, 'lesson_id' => $lesson->id,
            'checks' => [true, true, true], 'progress_percent' => 100, 'completed_at' => now()]);
        $relation = app(CourseRelationService::class)->save($this->admin, $course, [
            'related_course_series_id' => CourseSeries::whereKeyNot($course->id)->first()->id,
            'relation_type' => 'next', 'sort_order' => 1,
        ]);
        $content = $lesson->getAttributes();
        Livewire::test(ListCourseSeries::class)->callTableAction('delete', $course)->assertHasNoTableActionErrors();
        $this->assertSoftDeleted($course);
        $this->assertSame($content, $lesson->fresh()->getAttributes());
        $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id, 'progress_percent' => 100]);
        $this->assertDatabaseHas('course_relations', ['id' => $relation->id]);
        Livewire::test(ListCourseSeries::class)->assertCanNotSeeTableRecords([$course]);
        Livewire::test(ListLessons::class)->assertCanNotSeeTableRecords([$lesson]);
        $this->get('/galaxy/course-series/'.$course->slug.'/edit')->assertNotFound();
        $this->get('/galaxy/lessons/'.$lesson->id.'/edit')->assertNotFound();
        $this->get(route('courses.preview', $course))->assertNotFound();
        $this->get(route('courses.show', $course))->assertNotFound();
        $this->get(route('free.lesson', [$course, $lesson->slug]))->assertNotFound();
        $this->postJson(route('free.lesson', [$course, $lesson->slug]).'/progress', ['checks' => [true, true, true]])->assertNotFound();
        $this->get(route('free.resource', [$course, $lesson->slug, $lesson->resources[0]['name']]))->assertNotFound();
        $this->actingAs($learner)->get('/me')->assertOk()->assertSee('课程已下架')
            ->assertViewHas('freeProgress', fn ($records) => $records->contains('id', $progress->id));

        $this->actingAs($this->admin);
        $trash = Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash');
        $trash->assertCanSeeTableRecords([$course])->assertTableActionHidden('delete', $course)
            ->assertTableActionHidden('edit', $course)->assertTableActionHidden('frontendPreview', $course)
            ->assertTableActionVisible('restore', $course)->assertTableActionVisible('forceDelete', $course)
            ->callTableAction('restore', $course)->assertHasNoTableActionErrors();
        $restored = $course->fresh();
        $this->assertFalse($restored->trashed());
        $this->assertSame('draft', $restored->status);
        $this->get(route('free.lesson', [$course, $lesson->slug]))->assertNotFound();
        $restored->update(['status' => 'published']);
        $this->actingAs($learner)->get('/me')->assertViewHas('freeProgress', fn ($records) => $records->contains('id', $progress->id));
    }

    public function test_final_deletion_uses_a_confirmation_modal_and_removes_only_course_content_and_connections(): void
    {
        Storage::fake('public');
        $course = CourseSeries::first();
        $course->update(['cover' => 'course-covers/retained.jpg']);
        Storage::disk('public')->put($course->cover, 'shared uploaded cover');
        $other = CourseSeries::whereKeyNot($course->id)->first();
        $third = CourseSeries::whereKeyNot($course->id)->whereKeyNot($other->id)->first();
        $relations = app(CourseRelationService::class);
        foreach ([[$course, $other], [$other, $course], [$other, $third]] as [$source, $target]) {
            $relations->save($this->admin, $source, ['related_course_series_id' => $target->id, 'relation_type' => 'next', 'sort_order' => 1]);
        }
        $lessonIds = $course->lessons()->pluck('id');
        app(CourseDeletionService::class)->trash($this->admin, $course);
        $trash = Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash');
        $trash->mountTableAction('forceDelete', $course);
        $this->assertDatabaseHas('course_series', ['id' => $course->id]);
        $action = $trash->instance()->getTable()->getAction('forceDelete')->record($course);
        $this->assertTrue($action->isConfirmationRequired());
        $this->assertSame('确认', $action->getModalSubmitActionLabel());
        $trash->assertDontSee('输入课程地址标识确认')->callMountedTableAction()->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing('course_series', ['id' => $course->id]);
        $this->assertSame(0, Lesson::whereIn('id', $lessonIds)->count());
        $this->assertDatabaseCount('course_relations', 1);
        $this->assertDatabaseHas('course_relations', ['course_series_id' => $other->id, 'related_course_series_id' => $third->id]);
        $this->assertDatabaseHas('course_catalog_suppressions', ['slug' => $course->slug]);
        Storage::disk('public')->assertExists($course->cover);
        $this->assertSame('published', $other->fresh()->status);
        $this->assertSame(2, app(FreeLabInstaller::class)->install() + CourseSeries::count());
    }

    public function test_courses_with_learning_records_can_be_finally_deleted_without_losing_progress(): void
    {
        $course = CourseSeries::first();
        $lesson = $course->lessons()->first();
        $progress = LessonProgress::create(['user_id' => User::factory()->create()->id, 'lesson_id' => $lesson->id,
            'checks' => [false, false, false], 'progress_percent' => 0]);
        $before = $progress->fresh()->getAttributes();
        $edge = app(CourseRelationService::class)->save($this->admin, $course, [
            'related_course_series_id' => CourseSeries::whereKeyNot($course->id)->first()->id, 'relation_type' => 'next', 'sort_order' => 1,
        ]);
        app(CourseDeletionService::class)->trash($this->admin, $course);
        Livewire::test(ListCourseSeries::class)->set('activeTab', 'trash')
            ->callTableAction('forceDelete', $course)->assertNotified('课程已最终删除');
        $this->assertDatabaseMissing('course_series', ['id' => $course->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        $this->assertDatabaseHas('lesson_progress', ['id' => $progress->id, 'lesson_id' => null, 'progress_percent' => 0]);
        $this->assertSame($course->title, $progress->fresh()->deleted_course_snapshot['course_title']);
        foreach (['checks', 'progress_percent', 'last_position_seconds', 'completed_at', 'created_at', 'updated_at', 'user_id'] as $field) {
            $this->assertSame($before[$field], $progress->fresh()->getAttributes()[$field]);
        }
        $this->assertDatabaseMissing('course_relations', ['id' => $edge->id]);
        $this->assertDatabaseHas('course_catalog_suppressions', ['slug' => $course->slug]);
    }

    public function test_a_course_must_enter_the_recycle_bin_before_final_deletion(): void
    {
        $course = CourseSeries::first();
        $service = app(CourseDeletionService::class);
        $this->assertFalse(Gate::allows('forceDelete', $course));
        try {
            $service->permanentlyDelete($this->admin, $course);
            $this->fail('Active courses cannot be finally deleted.');
        } catch (AuthorizationException) {
            $this->assertDatabaseHas('course_series', ['id' => $course->id, 'deleted_at' => null]);
        }
    }

    public function test_ordinary_users_cannot_delete_restore_or_finally_delete_even_after_a_modal_was_opened(): void
    {
        $course = CourseSeries::first();
        $table = Livewire::test(ListCourseSeries::class)->mountTableAction('delete', $course);
        $ordinary = User::factory()->create();
        $this->actingAs($ordinary);
        $table->callMountedTableAction()->assertForbidden();
        $this->assertDatabaseHas('course_series', ['id' => $course->id, 'deleted_at' => null]);
        $service = app(CourseDeletionService::class);
        $service->trash($this->admin, $course);
        $trashed = $course->fresh();
        foreach (['restore', 'permanentlyDelete'] as $method) {
            try {
                $service->$method($ordinary, $trashed);
                $this->fail('Ordinary users cannot manage the recycle bin.');
            } catch (AuthorizationException) {
                $this->assertSoftDeleted($course);
            }
        }
        $this->get('/galaxy/course-series')->assertForbidden();
    }

    public function test_recycled_courses_are_excluded_from_legacy_routes_and_cannot_reappear_after_final_deletion_or_import(): void
    {
        app(LegacyCourseImporter::class)->run();
        $website = CourseSeries::where('slug', 'build-a-website')->first();
        $service = app(CourseDeletionService::class);
        $service->trash($this->admin, $website);
        $this->assertLegacyWebsiteHidden();
        $this->assertSame(0, app(LegacyCourseImporter::class)->run()['created']);
        $this->assertSame(1, CourseSeries::onlyTrashed()->where('slug', $website->slug)->count());
        $service->restore($this->admin, $website->fresh());
        $this->get('/series/build-a-website')->assertNotFound();
        $service->trash($this->admin, $website->fresh());
        $service->permanentlyDelete($this->admin, $website->fresh());
        $this->assertLegacyWebsiteHidden();
        $this->assertSame(0, app(LegacyCourseImporter::class)->run()['created']);
        CourseSeries::create(['slug' => $website->slug, 'title' => '明确新建的网站课程', 'category' => 'create']);
        $this->assertDatabaseMissing('course_catalog_suppressions', ['slug' => $website->slug]);
        $this->get('/series/build-a-website')->assertNotFound();
    }

    private function assertLegacyWebsiteHidden(): void
    {
        $this->get('/')->assertOk()->assertDontSee('href="'.route('series.show', 'build-a-website').'"', false)
            ->assertDontSee('href="'.route('lessons.show', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']).'"', false);
        $this->get('/series')->assertOk()->assertViewHas('series', fn ($courses) => ! collect($courses)->contains('slug', 'build-a-website'));
        $this->get('/series/build-a-website')->assertNotFound();
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertNotFound();
        $this->get(route('lessons.checklist', ['slug' => 'build-a-website', 'lessonSlug' => 'server-and-ip']))->assertNotFound();
        $this->get('/me')->assertOk()->assertViewHas('series', fn ($courses) => ! collect($courses)->contains('slug', 'build-a-website'));
    }

    public function test_deleted_targets_are_not_publicly_presented_and_cannot_be_newly_linked(): void
    {
        $source = CourseSeries::first();
        $target = CourseSeries::whereKeyNot($source->id)->first();
        $data = ['related_course_series_id' => $target->id, 'relation_type' => 'next', 'sort_order' => 1];
        app(CourseRelationService::class)->save($this->admin, $source, $data);
        app(CourseDeletionService::class)->trash($this->admin, $target);
        $this->get(route('series.show', $source))->assertOk()->assertDontSee('href="'.route('courses.show', $target).'"', false);
        $this->get(route('courses.preview', $source))->assertOk();
        try {
            app(CourseRelationService::class)->save($this->admin, $source, array_replace($data, ['relation_type' => 'recommended']));
            $this->fail('A new relation cannot target a recycled course.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('related_course_series_id', $exception->errors());
            $this->assertDatabaseCount('course_relations', 1);
        }
    }

    public function test_stale_course_objects_cannot_authorize_learning_or_modify_trashed_lessons(): void
    {
        $course = CourseSeries::first();
        $lesson = $course->lessons()->first();
        app(CourseDeletionService::class)->trash($this->admin, $course);
        $this->assertFalse(app(CourseAccessService::class)->canAccess($course, $lesson));
        $this->assertFalse(Gate::allows('update', $lesson));
        try {
            $lesson->update(['title' => '应被拒绝']);
            $this->fail('Trashed course lessons cannot be edited.');
        } catch (ValidationException) {
            $this->assertNotSame('应被拒绝', $lesson->fresh()->title);
        }
        Livewire::test(ListCourseSeries::class)->set('activeTab', 'unknown')->assertCanNotSeeTableRecords([$course]);
    }

    public function test_edit_page_has_confirmed_delete_action_and_returns_to_course_list(): void
    {
        $course = CourseSeries::first();
        Livewire::test(EditCourseSeries::class, ['record' => $course->slug])->callAction('delete')->assertRedirect();
        $this->assertSoftDeleted($course);
        $this->assertDatabaseCount('lessons', 3);
    }
}
