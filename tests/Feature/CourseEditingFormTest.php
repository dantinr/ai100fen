<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\CreateCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Models\CourseRelation;
use App\Models\CourseSeries;
use App\Models\User;
use App\Services\CourseRelationService;
use App\Services\FreeLabInstaller;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseEditingFormTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        app(FreeLabInstaller::class)->install();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
    }

    private function relation(CourseSeries $source, CourseSeries $target, string $type): CourseRelation
    {
        return app(CourseRelationService::class)->save($this->admin, $source, [
            'related_course_series_id' => $target->id, 'relation_type' => $type,
            'sort_order' => 50, 'description' => '已有的关联理由',
        ]);
    }

    public function test_edit_form_saves_preparation_and_both_relation_types_without_changing_recommendations(): void
    {
        [$a, $b, $c] = CourseSeries::orderBy('id')->get()->all();
        $recommended = $this->relation($a, $b, 'recommended');
        $before = $recommended->fresh()->getAttributes();
        $form = Livewire::test(EditCourseSeries::class, ['record' => $a->slug])
            ->fillForm([
                'description' => '这门课程帮助你做成一个真实作品。', 'final_outcome' => '一个可以打开并展示的真实作品',
                'recommendation_keywords' => ['网页', '作品'],
                'prerequisites' => ['准备一个浏览器', '准备自己的真实资料'],
                'prerequisite_courses' => [['related_course_series_id' => $b->id, 'sort_order' => 20, 'description' => '先完成一个小任务']],
                'next_courses' => [['related_course_series_id' => $c->id, 'sort_order' => 10, 'description' => '继续验证成果']],
            ])->call('save')->assertHasNoFormErrors();
        $this->assertSame(['准备一个浏览器', '准备自己的真实资料'], $a->fresh()->prerequisites);
        $this->assertSame('一个可以打开并展示的真实作品', $a->fresh()->final_outcome);
        $this->assertSame(['网页', '作品'], $a->fresh()->recommendation_keywords);
        $this->assertSame($before, $recommended->fresh()->getAttributes());
        $this->assertDatabaseHas('course_relations', ['course_series_id' => $a->id, 'related_course_series_id' => $b->id, 'relation_type' => 'prerequisite', 'sort_order' => 20]);
        $this->assertDatabaseHas('course_relations', ['course_series_id' => $a->id, 'related_course_series_id' => $c->id, 'relation_type' => 'next', 'sort_order' => 10]);
        $form->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseCount('course_relations', 3);
        $this->get(route('series.show', $a))->assertOk()->assertSee('准备一个浏览器')->assertSee('先完成一个小任务')->assertSee('继续验证成果')
            ->assertSee('这门课程帮助你做成一个真实作品。')->assertSee('一个可以打开并展示的真实作品');
        config(['themes.active' => 'future']);
        $this->get(route('series.show', $a))->assertOk()->assertSee('准备自己的真实资料');
    }

    public function test_new_course_can_save_preparation_and_relations_in_one_submission(): void
    {
        [$a, $b] = CourseSeries::orderBy('id')->get()->take(2)->all();
        Livewire::test(CreateCourseSeries::class)->fillForm([
            'title' => '制作一个可打开的任务网页', 'slug' => 'new-editable-task', 'category' => 'create',
            'prerequisites' => ['准备作品素材'],
            'prerequisite_courses' => [['related_course_series_id' => $a->id, 'sort_order' => 10]],
            'next_courses' => [['related_course_series_id' => $b->id, 'sort_order' => 20]],
        ])->call('create')->assertHasNoFormErrors();
        $course = CourseSeries::where('slug', 'new-editable-task')->firstOrFail();
        $this->assertSame('draft', $course->status);
        $this->assertSame(['准备作品素材'], $course->prerequisites);
        $this->assertCount(2, $course->courseRelations);
        $this->get(route('series.show', $course))->assertNotFound();
    }

    public function test_cycle_rejection_rolls_back_course_fields_and_the_entire_relation_change(): void
    {
        [$a, $b, $c] = CourseSeries::orderBy('id')->get()->all();
        $this->relation($b, $a, 'prerequisite');
        $next = $this->relation($a, $c, 'next');
        $title = $a->title;
        $form = Livewire::test(EditCourseSeries::class, ['record' => $a->slug])->fillForm([
            'title' => '这个改名不能被部分保存', 'prerequisites' => ['不能部分保存'],
            'prerequisite_courses' => [['related_course_series_id' => $b->id, 'sort_order' => 10]],
            'next_courses' => [],
        ]);
        $key = array_key_first($form->get('data.prerequisite_courses'));
        $form->call('save')->assertHasFormErrors(['prerequisite_courses.'.$key.'.related_course_series_id']);
        $this->assertSame($title, $a->fresh()->title);
        $this->assertSame([], $a->fresh()->prerequisites);
        $this->assertDatabaseHas('course_relations', ['id' => $next->id]);
        $this->assertDatabaseCount('course_relations', 2);
    }

    public function test_stale_form_cannot_overwrite_relations_added_elsewhere(): void
    {
        [$a, $b] = CourseSeries::orderBy('id')->get()->take(2)->all();
        $title = $a->title;
        $form = Livewire::test(EditCourseSeries::class, ['record' => $a->slug]);
        $edge = $this->relation($a, $b, 'next');
        $form->fillForm(['title' => '不覆盖其他窗口的关联'])->call('save')->assertHasFormErrors(['next_courses']);
        $this->assertSame($title, $a->fresh()->title);
        $this->assertDatabaseHas('course_relations', ['id' => $edge->id]);
    }

    public function test_foreign_relation_ids_and_invalid_targets_cannot_be_saved(): void
    {
        [$a, $b, $c] = CourseSeries::orderBy('id')->get()->all();
        $foreign = $this->relation($b, $c, 'next');
        foreach ([
            [['id' => $foreign->id, 'related_course_series_id' => $b->id, 'sort_order' => 10]],
            [['related_course_series_id' => $a->id, 'sort_order' => 10]],
            [['related_course_series_id' => 999999, 'sort_order' => 10]],
            [['related_course_series_id' => $b->id, 'sort_order' => 10], ['related_course_series_id' => $b->id, 'sort_order' => 20]],
        ] as $rows) {
            Livewire::test(EditCourseSeries::class, ['record' => $a->slug])->fillForm(['next_courses' => $rows])
                ->call('save')->assertHasFormErrors();
            $this->assertDatabaseCount('course_relations', 1);
            $this->assertSame($b->id, $foreign->fresh()->course_series_id);
        }
    }

    public function test_relation_removal_requires_confirmation_and_only_applies_when_saved(): void
    {
        [$a, $b] = CourseSeries::orderBy('id')->get()->take(2)->all();
        $edge = $this->relation($a, $b, 'next');
        $before = $b->getAttributes();
        $form = Livewire::test(EditCourseSeries::class, ['record' => $a->slug])
            ->assertFormFieldExists('next_courses', fn (Repeater $field) => $field->getDeleteAction()->isConfirmationRequired());
        $key = array_key_first($form->get('data.next_courses'));
        $form->mountFormComponentAction('next_courses', 'delete', arguments: ['item' => $key]);
        $this->assertDatabaseHas('course_relations', ['id' => $edge->id]);
        $form->unmountAction()->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseHas('course_relations', ['id' => $edge->id]);
        $key = array_key_first($form->get('data.next_courses'));
        $form->callFormComponentAction('next_courses', 'delete', arguments: ['item' => $key]);
        $this->assertDatabaseHas('course_relations', ['id' => $edge->id]);
        $form->call('save')->assertHasNoFormErrors();
        $this->assertDatabaseMissing('course_relations', ['id' => $edge->id]);
        $this->assertSame($before, $b->fresh()->getAttributes());
    }

    public function test_permission_revoked_after_opening_form_prevents_saving(): void
    {
        $a = CourseSeries::firstOrFail();
        $title = $a->title;
        $form = Livewire::test(EditCourseSeries::class, ['record' => $a->slug]);
        User::whereKey($this->admin->id)->update(['is_admin' => false]);
        $form->fillForm(['title' => '权限撤销后不能保存'])->call('save')->assertForbidden();
        $this->assertSame($title, $a->fresh()->title);
    }
}
