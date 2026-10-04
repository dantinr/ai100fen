<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Filament\Resources\CourseSeries\RelationManagers\CourseRelationsRelationManager;
use App\Models\CourseRelation;
use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseRelationService;
use App\Services\FreeLabInstaller;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CourseRelationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        $this->admin = User::factory()->create();
        $this->admin->forceFill(['is_admin' => true])->save();
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
    }

    private function save(CourseSeries $source, CourseSeries $target, string $type = 'next', int $order = 1000, ?string $reason = null): CourseRelation
    {
        return app(CourseRelationService::class)->save($this->admin, $source, [
            'related_course_series_id' => $target->id, 'relation_type' => $type, 'sort_order' => $order, 'description' => $reason,
        ]);
    }

    private function paidCourse(string $slug = 'paid-result'): CourseSeries
    {
        $original = CourseSeries::first();
        $copy = $original->replicate();
        $copy->fill(['slug' => $slug, 'title' => '一个已发布的完整作品任务', 'is_free' => false, 'price' => '100.00', 'status' => 'draft'])->save();
        $lesson = $original->lessons->first()->replicate();
        $lesson->fill(['course_series_id' => $copy->id, 'prompt' => 'PRIVATE_PAID_PROMPT', 'content' => 'PRIVATE_PAID_CONTENT', 'code' => 'PRIVATE_PAID_CODE'])->save();
        $copy->update(['status' => 'published']);

        return $copy;
    }

    public function test_admin_can_create_edit_sort_and_remove_relations_without_changing_courses_or_progress(): void
    {
        $this->actingAs($this->admin);
        $courses = CourseSeries::orderBy('id')->get();
        $progress = LessonProgress::create(['user_id' => $this->admin->id, 'lesson_id' => $courses[0]->lessons->first()->id,
            'checks' => [true, true, true], 'progress_percent' => 100, 'completed_at' => now()]);
        $progressBefore = $progress->fresh()->getAttributes();
        $before = $courses->map->getAttributes()->all();
        $lessonsBefore = $courses->flatMap->lessons->map->getAttributes()->all();
        $manager = Livewire::test(CourseRelationsRelationManager::class, ['ownerRecord' => $courses[0], 'pageClass' => EditCourseSeries::class]);
        $manager->callTableAction('create', data: [
            'related_course_series_id' => $courses[1]->id, 'relation_type' => 'next', 'sort_order' => 20, 'description' => '继续完成一个可验收任务',
        ])->assertHasNoTableActionErrors();
        $first = CourseRelation::firstOrFail();
        $this->assertSame($courses[0]->id, $first->course_series_id);
        $second = $this->save($courses[0], $courses[2], order: 10);
        $manager->call('resetTable')->assertCanSeeTableRecords([$second, $first], inOrder: true);
        $manager->callTableAction('edit', $first, ['sort_order' => 0, 'description' => '先做这一项'])->assertHasNoTableActionErrors();
        $this->assertSame(0, $first->fresh()->sort_order);
        $this->assertSame('先做这一项', $first->fresh()->description);
        $manager->assertCanSeeTableRecords([$first, $second], inOrder: true);
        $manager->callTableAction('delete', $first)->assertHasNoTableActionErrors();
        $this->assertDatabaseMissing('course_relations', ['id' => $first->id]);
        $this->assertSame($before, CourseSeries::orderBy('id')->get()->map->getAttributes()->all());
        $this->assertSame($lessonsBefore, CourseSeries::orderBy('id')->get()->flatMap->lessons->map->getAttributes()->all());
        $this->assertSame($progressBefore, $progress->fresh()->getAttributes());
        $this->assertDatabaseCount('lesson_progress', 1);
    }

    public function test_relation_forms_reject_self_duplicates_invalid_types_and_invalid_order(): void
    {
        $this->actingAs($this->admin);
        $courses = CourseSeries::orderBy('id')->get();
        $data = ['related_course_series_id' => $courses[1]->id, 'relation_type' => 'recommended', 'sort_order' => 10];
        $this->save($courses[0], $courses[1], 'recommended');
        foreach ([
            [[], 'related_course_series_id'],
            [['related_course_series_id' => $courses[0]->id], 'related_course_series_id'],
            [['related_course_series_id' => 99999], 'related_course_series_id'],
            [['relation_type' => 'unknown'], 'relation_type'],
            [['relation_type' => 'next', 'sort_order' => -1], 'sort_order'],
            [['relation_type' => 'next', 'sort_order' => '0.5'], 'sort_order'],
            [['relation_type' => 'next', 'sort_order' => 1000000], 'sort_order'],
        ] as [$override, $field]) {
            Livewire::test(CourseRelationsRelationManager::class, ['ownerRecord' => $courses[0], 'pageClass' => EditCourseSeries::class])
                ->callTableAction('create', data: array_replace($data, $override))->assertHasTableActionErrors([$field]);
        }
        $this->assertDatabaseCount('course_relations', 1);
        // Different relationship types are independently meaningful; recommendations are directional.
        $this->save($courses[0], $courses[1], 'next');
        $this->save($courses[1], $courses[0], 'recommended');
        $this->assertDatabaseCount('course_relations', 3);
    }

    public function test_transitive_prerequisite_cycles_are_rejected_on_create_and_edit_and_can_be_corrected(): void
    {
        $this->actingAs($this->admin);
        [$a, $b, $c] = CourseSeries::orderBy('id')->get()->all();
        $this->save($a, $b, 'prerequisite');
        $this->save($b, $c, 'prerequisite');
        $manager = Livewire::test(CourseRelationsRelationManager::class, ['ownerRecord' => $c, 'pageClass' => EditCourseSeries::class]);
        $manager->callTableAction('create', data: ['related_course_series_id' => $a->id, 'relation_type' => 'prerequisite', 'sort_order' => 0])
            ->assertHasTableActionErrors(['related_course_series_id']);
        $manager->unmountTableAction();
        $edge = $this->save($c, $a, 'recommended');
        $manager->callTableAction('edit', $edge, ['relation_type' => 'prerequisite'])->assertHasTableActionErrors(['related_course_series_id']);
        $this->assertSame('recommended', $edge->fresh()->relation_type);
        $existing = $a->courseRelations()->first();
        app(CourseRelationService::class)->save($this->admin, $a, ['related_course_series_id' => $c->id, 'relation_type' => 'prerequisite', 'sort_order' => 5], $existing);
        $this->assertSame($c->id, $existing->fresh()->related_course_series_id);
    }

    public function test_normal_users_and_stale_admin_actions_cannot_mutate_relations(): void
    {
        $courses = CourseSeries::orderBy('id')->get();
        $edge = $this->save($courses[0], $courses[1]);
        $this->actingAs($this->admin);
        $manager = Livewire::test(CourseRelationsRelationManager::class, ['ownerRecord' => $courses[0], 'pageClass' => EditCourseSeries::class]);
        $manager->mountTableAction('delete', $edge);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/galaxy/course-series/'.$courses[0]->slug.'/edit')->assertForbidden();
        $manager->call('callMountedAction')->assertForbidden();
        foreach (['save', 'remove'] as $operation) {
            try {
                $service = app(CourseRelationService::class);
                $operation === 'save' ? $service->save($user, $courses[0], []) : $service->remove($user, $courses[0], $edge);
                $this->fail('Course relation writes require an administrator.');
            } catch (AuthorizationException) {
                $this->assertDatabaseHas('course_relations', ['id' => $edge->id]);
            }
        }
    }

    public function test_foreign_course_relation_ids_cannot_be_edited_or_removed(): void
    {
        [$a, $b, $c] = CourseSeries::orderBy('id')->get()->all();
        $edge = $this->save($a, $b);
        foreach (['save', 'remove'] as $operation) {
            try {
                $service = app(CourseRelationService::class);
                $operation === 'save' ? $service->save($this->admin, $c, [], $edge) : $service->remove($this->admin, $c, $edge);
                $this->fail('Foreign relation IDs must be rejected.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
                $this->assertSame($a->id, $edge->fresh()->course_series_id);
            }
        }
    }

    public function test_public_relations_link_free_and_paid_overviews_without_exposing_paid_lessons_or_drafts(): void
    {
        $courses = CourseSeries::orderBy('id')->get();
        $free = $courses[0];
        $paid = $this->paidCourse();
        $draft = CourseSeries::create(['slug' => 'private-draft', 'title' => 'PRIVATE_DRAFT_TITLE', 'category' => 'create']);
        $archived = $courses[2];
        $archived->update(['status' => 'archived']);
        $this->save($free, $paid, order: 0, reason: '继续制作一个完整作品');
        $this->save($free, $draft, reason: 'PRIVATE_DRAFT_REASON');
        $this->save($free, $archived, reason: 'PRIVATE_ARCHIVED_REASON');
        $this->save($paid, $free, 'prerequisite');
        $lesson = $free->lessons->first();
        foreach ([route('free.lesson', [$free, $lesson->slug]), route('courses.show', $free)] as $url) {
            $this->get($url)->assertOk()->assertSee('下一步课程')->assertSee('继续制作一个完整作品')
                ->assertSee(route('courses.show', $paid))->assertDontSee('PRIVATE_DRAFT')->assertDontSee('PRIVATE_ARCHIVED');
        }
        $this->get(route('courses.show', $paid))->assertOk()->assertSee('前置课程')->assertSee($free->title)
            ->assertSee('完整课程学习与购买暂未开放')->assertDontSee('PRIVATE_PAID');
        $this->get(route('courses.show', $free))->assertSee(route('free.lesson', [$free, $lesson->slug]));
        config(['themes.active' => 'future']);
        $this->get(route('courses.show', $free))->assertOk()->assertSee('data-theme="future"', false)->assertSee('下一步课程');
        $this->get(route('courses.show', $draft))->assertNotFound();
        $this->get(route('courses.show', $archived))->assertNotFound();
        $this->get('/lab/'.$paid->slug.'/'.$paid->lessons->first()->slug)->assertNotFound();
        $this->assertDatabaseCount('lesson_progress', 0);
        $paid->update(['status' => 'archived']);
        $this->get(route('courses.show', $free))->assertDontSee('data-course-relations', false);
    }

    public function test_legacy_details_only_show_relations_for_a_published_source_and_admin_preview_remains_private(): void
    {
        $free = CourseSeries::first();
        $legacy = $this->paidCourse('build-a-website');
        $this->save($legacy, $free, 'recommended', reason: '<script>alert("x")</script>');
        $this->get('/series/build-a-website')->assertOk()->assertSee('推荐课程')->assertSee($free->title)
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("x")</script>', false);
        $legacy->update(['status' => 'draft']);
        $this->get('/series/build-a-website')->assertOk()->assertDontSee('data-course-relations', false);
        $this->get(route('courses.preview', $legacy))->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get(route('courses.preview', $legacy))->assertForbidden();
        $this->actingAs($this->admin)->get(route('courses.preview', $legacy))->assertOk()->assertSee('管理员预览')
            ->assertSee(route('courses.preview', $free))->assertHeader('Cache-Control', 'no-store, private');
    }
}
