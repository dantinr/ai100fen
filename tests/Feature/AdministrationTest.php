<?php

namespace Tests\Feature;

use App\Filament\Pages\ThemeSettings;
use App\Filament\Resources\CourseSeries\Pages\CreateCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Filament\Resources\CourseSeries\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Resources\Lessons\Pages\CreateLesson;
use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\ThemeSetting;
use App\Models\User;
use App\Services\CourseOutlineService;
use App\Services\CourseDisplayReorderService;
use App\Services\FreeLabInstaller;
use App\Services\ThemeConfiguration;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class AdministrationTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();

        return $admin;
    }

    public function test_panel_requires_an_explicit_administrator_even_locally(): void
    {
        $paths = ['/galaxy', '/galaxy/course-series', '/galaxy/course-series/create', '/galaxy/lessons', '/galaxy/theme-settings'];
        foreach ($paths as $path) {
            $this->get($path)->assertRedirect('/galaxy/login');
        }
        $this->actingAs(User::factory()->create());
        foreach ($paths as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->administrator();
        foreach ($paths as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_top_horizontal_scrollbar_is_scoped_to_course_and_lesson_lists(): void
    {
        $this->administrator();

        $this->get('/galaxy/course-series')->assertOk()->assertSee('课程列表横向滚动');
        $this->get('/galaxy/lessons')->assertOk()->assertSee('课时列表横向滚动')->assertDontSee('课程列表横向滚动');
        $this->get('/galaxy/course-series/create')->assertOk()->assertDontSee('课程列表横向滚动')->assertDontSee('课时列表横向滚动');
        $this->get('/galaxy/lessons/create')->assertOk()->assertDontSee('课时列表横向滚动');
        $this->get('/galaxy/theme-settings')->assertOk()->assertDontSee('课程列表横向滚动')->assertDontSee('课时列表横向滚动');
    }

    public function test_registration_cannot_grant_admin_access_and_cli_requires_confirmation(): void
    {
        $this->post('/register', ['name' => 'Learner', 'email' => 'learner@example.test', 'password' => 'Avalidpassword1', 'password_confirmation' => 'Avalidpassword1', 'is_admin' => true])->assertRedirect('/me');
        $this->assertFalse(User::first()->is_admin);
        $this->artisan('admin:set learner@example.test')->expectsConfirmation('Grant this account administrator access?', 'no')->assertFailed();
        $this->assertFalse(User::first()->is_admin);
        $this->artisan('admin:set learner@example.test')->expectsConfirmation('Grant this account administrator access?', 'yes')->assertSuccessful();
        $this->assertTrue(User::first()->is_admin);
        $this->artisan('admin:set learner@example.test --revoke')->expectsConfirmation('Revoke administrator access?', 'yes')->assertSuccessful();
        $this->assertFalse(User::first()->is_admin);
    }

    public function test_admin_can_create_draft_then_edit_goals_outline_and_publish_a_complete_course(): void
    {
        $this->administrator();
        Livewire::test(CreateCourseSeries::class)->fillForm([
            'title' => '做一个能打开的任务网页', 'slug' => 'admin-task-page', 'category' => 'create', 'is_free' => true, 'minutes' => 10,
        ])->call('create')->assertHasNoFormErrors();
        $series = CourseSeries::where('slug', 'admin-task-page')->firstOrFail();
        $this->assertSame('draft', $series->status);
        $this->assertSame('0.00', $series->price);
        $this->get('/lab')->assertDontSee($series->title);
        Livewire::test(CreateLesson::class)->fillForm([
            'course_series_id' => $series->id, 'title' => '生成并验收网页', 'slug' => 'make-and-check',
            'score' => 100, 'points' => 100, 'goal' => '打开网页并核对真实信息', 'prompt' => '生成个人介绍网页',
            'steps' => [['title' => '定义成果', 'body' => '确定页面显示哪些内容']],
            'checks' => ['网页能打开'], 'status' => 'published',
        ])->call('create')->assertHasNoFormErrors();
        Livewire::test(EditCourseSeries::class, ['record' => $series->slug])->fillForm([
            'user_intent' => '展示自己', 'final_outcome' => '一个可打开的网页', 'objectives' => ['生成真实成品'],
            'completion_criteria' => ['网页能打开'], 'agent_role' => ['生成文件'], 'human_judgment_required' => ['核对真实内容'], 'status' => 'published',
        ])->call('save')->assertHasNoFormErrors();
        $this->assertSame('published', $series->fresh()->status);
        $this->get('/lab/admin-task-page/make-and-check')->assertOk()->assertSee('生成真实成品');
    }

    public function test_invalid_publication_shows_a_form_error_and_does_not_save(): void
    {
        $this->administrator();
        $series = CourseSeries::create(['slug' => 'empty-course', 'title' => '做一个网页', 'category' => 'create']);
        Livewire::test(EditCourseSeries::class, ['record' => $series->slug])->fillForm([
            'status' => 'published', 'user_intent' => '展示作品', 'final_outcome' => '真实网页',
            'completion_criteria' => ['可打开'], 'agent_role' => ['生成网页'], 'human_judgment_required' => ['检查内容'],
        ])->call('save')->assertHasFormErrors(['status']);
        $this->assertSame('draft', $series->fresh()->status);
    }

    public function test_admin_can_publish_a_course_with_only_its_first_lesson_published(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();

        foreach ([false, true] as $isFree) {
            $series = CourseSeries::first()->replicate();
            $series->fill(['slug' => $isFree ? 'gradual-free' : 'gradual-paid', 'status' => 'draft',
                'is_free' => $isFree, 'price' => $isFree ? 0 : 100])->save();
            $first = Lesson::first()->replicate();
            $first->fill(['course_series_id' => $series->id, 'score' => 10, 'points' => 10])->save();
            $last = $first->replicate();
            $last->fill(['slug' => 'final-check', 'position' => 10, 'score' => 100, 'points' => 10,
                'status' => 'draft', 'goal' => '', 'prompt' => '', 'steps' => [], 'checks' => []])->save();

            Livewire::test(EditCourseSeries::class, ['record' => $series->slug])
                ->fillForm(['status' => 'published'])->call('save')->assertHasNoFormErrors();
            $this->assertSame('published', $series->fresh()->status);
            $this->assertSame(10, $first->fresh()->score);
            $this->assertSame('draft', $last->fresh()->status);
            Livewire::test(EditLesson::class, ['record' => $last->id])
                ->fillForm(['status' => 'published'])->call('save')->assertHasFormErrors(['goal', 'prompt']);
            $this->assertSame('draft', $last->fresh()->status);
            $this->assertSame('published', $series->fresh()->status);
        }
    }

    public function test_a_course_with_only_draft_or_archived_lessons_cannot_be_published(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $lesson = $series->lessons()->first();

        foreach (['draft', 'archived'] as $status) {
            $lesson->update(['status' => $status]);
            Livewire::test(EditCourseSeries::class, ['record' => $series->slug])
                ->fillForm(['status' => 'published'])->call('save')->assertHasFormErrors(['status']);
            $this->assertSame('draft', $series->fresh()->status);
        }
    }

    public function test_admin_can_edit_display_order_in_actions_and_forms_with_validation_and_policy_checks(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();
        $courses = CourseSeries::orderBy('id')->get();
        $last = $courses->last();
        $table = Livewire::test(ListCourseSeries::class)->assertTableColumnExists('sort_order');
        $table->callTableAction('displayOrder', $last, ['sort_order' => '0'])->assertHasNoTableActionErrors();
        $this->assertSame(0, $last->fresh()->sort_order);
        $this->assertSame('published', $last->fresh()->status);
        Livewire::test(ListCourseSeries::class)->assertCanSeeTableRecords([$last, $courses[0], $courses[1]], inOrder: true);
        foreach ([-1, '0.5', 1000000, ''] as $invalid) {
            Livewire::test(ListCourseSeries::class)->callTableAction('displayOrder', $last, ['sort_order' => $invalid])
                ->assertHasTableActionErrors(['sort_order']);
            $this->assertSame(0, $last->fresh()->sort_order);
        }
        Livewire::test(EditCourseSeries::class, ['record' => $last->slug])->fillForm(['sort_order' => 25])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame(25, $last->fresh()->sort_order);
        Livewire::test(EditCourseSeries::class, ['record' => $last->slug])->fillForm(['sort_order' => -1])
            ->call('save')->assertHasFormErrors(['sort_order']);

        $table->mountTableAction('displayOrder', $last)->setTableActionData(['sort_order' => 1]);
        $this->actingAs(User::factory()->create());
        $this->get('/galaxy/course-series')->assertForbidden();
        $table->call('callMountedAction')->assertForbidden();
        $this->assertSame(25, $last->fresh()->sort_order);
    }

    public function test_admin_can_drag_all_courses_to_reorder_frontend_without_changing_content(): void
    {
        $admin = $this->administrator();
        app(FreeLabInstaller::class)->install();
        $courses = CourseSeries::orderBy('id')->get();
        $before = $courses->mapWithKeys(fn (CourseSeries $course) => [$course->id => [
            'status' => $course->status, 'title' => $course->title,
            'lesson_count' => $course->lessons()->count(),
        ]])->all();
        $order = [$courses[2]->id, $courses[0]->id, $courses[1]->id];

        $table = Livewire::test(ListCourseSeries::class);
        $this->assertTrue($table->instance()->getTable()->isReorderable());
        $table->call('reorderTable', $order)->assertHasNoErrors();
        $this->assertSame($order, CourseSeries::displayOrder()->pluck('id')->all());
        $this->assertSame([1, 2, 3], CourseSeries::displayOrder()->pluck('sort_order')->all());
        $this->get('/lab')->assertViewHas('courses', fn ($visible) => $visible->pluck('id')->all() === $order);
        foreach ($courses as $course) {
            $fresh = $course->fresh();
            $this->assertSame($before[$course->id]['status'], $fresh->status);
            $this->assertSame($before[$course->id]['title'], $fresh->title);
            $this->assertSame($before[$course->id]['lesson_count'], $fresh->lessons()->count());
        }

        try {
            app(CourseDisplayReorderService::class)->reorder($admin, [$order[1], $order[0]]);
            $this->fail('Partial course order must be rejected.');
        } catch (ValidationException) {
            $this->assertSame($order, CourseSeries::displayOrder()->pluck('id')->all());
        }

        $this->actingAs(User::factory()->create());
        $table->call('reorderTable', array_reverse($order))->assertForbidden();
        $this->assertSame($order, CourseSeries::displayOrder()->pluck('id')->all());
    }

    public function test_course_and_lesson_editors_render_existing_arrays_and_save_markdown_safely(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $lesson = $series->lessons->first();
        $lessonListUrl = LessonResource::getUrl('index', ['filters' => ['course_series_id' => ['value' => $series->id]]]);
        Livewire::test(ListCourseSeries::class)->assertTableColumnExists('id')->assertTableActionHasUrl('manageLessons', $lessonListUrl, $series);
        Livewire::test(ListCourseSeries::class)->assertTableActionHasUrl('frontendPreview', route('courses.preview', $series), $series);
        Livewire::withQueryParams(['filters' => ['course_series_id' => ['value' => $series->id]]])->test(ListLessons::class)
            ->assertCanSeeTableRecords([$lesson])
            ->assertCanNotSeeTableRecords(Lesson::where('course_series_id', '!=', $series->id)->get());
        $this->get('/galaxy/course-series/'.$series->slug.'/edit')->assertOk()->assertSee('课程大纲');
        $this->get('/galaxy/lessons/'.$lesson->id.'/edit')->assertOk()->assertSee('课时大纲');
        Livewire::test(EditLesson::class, ['record' => $lesson->id])->fillForm([
            'objectives' => ['完成真实网页'], 'content' => "## 正文目标\n\n<script>evil()</script>\n\n[危险](javascript:alert(1))", 'video_url' => 'https://example.test/video',
        ])->call('save')->assertHasNoFormErrors();
        $this->get('/lab/'.$series->slug.'/'.$lesson->slug)->assertOk()->assertSee('正文目标')->assertSee('完成真实网页')->assertDontSee('<script>evil()</script>', false)->assertDontSee('href="javascript:', false);
    }

    public function test_frontend_preview_is_admin_only_read_only_and_supports_unpublished_paid_courses(): void
    {
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $series->update(['status' => 'draft', 'is_free' => false, 'price' => '100.00']);
        $first = $series->lessons()->first();
        $draft = $first->replicate();
        $draft->fill(['slug' => 'draft-step', 'title' => '未发布的课时', 'status' => 'draft', 'position' => 2])->save();
        $url = route('courses.preview', $series);

        $this->get($url)->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
        $this->administrator();
        $this->get($url)->assertOk()->assertSee('管理员前台预览')->assertSee('付费课程')->assertSee('未发布的课时')
            ->assertSee('data-page="lesson"', false)
            ->assertDontSee('data-free-progress', false)->assertDontSee('保存验收进度')->assertDontSee(route('free.resource', [$series, $first->slug, $first->resources[0]['name']]))
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('courses.preview', [$series, $draft->slug]))->assertOk()->assertSee($draft->title);
        $this->get(route('courses.preview', [$series, 'not-in-this-course']))->assertNotFound();
        $this->get(route('free.lesson', [$series, $first->slug]))->assertNotFound();
        $this->assertDatabaseCount('lesson_progress', 0);
        $this->assertSame('draft', $series->fresh()->status);
        $empty = CourseSeries::create(['slug' => 'empty-preview', 'title' => '没有课时的课程', 'category' => 'solve']);
        $this->get(route('courses.preview', $empty))->assertOk()->assertSee('还没有课时')->assertSee('添加课时');
    }

    public function test_outline_reordering_is_scoped_authorized_and_requires_republication(): void
    {
        $admin = $this->administrator();
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $first = $series->lessons->first();
        $first->update(['points' => 40, 'score' => 40]);
        $second = $first->replicate();
        $second->fill(['slug' => 'final', 'position' => 2, 'points' => 60, 'score' => 100])->save();
        $series->refresh()->update(['status' => 'published']);
        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $series, 'pageClass' => EditCourseSeries::class])
            ->assertCanSeeTableRecords([$first, $second])->call('reorderTable', [$second->id, $first->id]);
        $this->assertSame(1, $second->fresh()->position);
        $this->assertSame('draft', $series->fresh()->status);
        $this->get('/lab/'.$series->slug.'/'.$first->slug)->assertNotFound();
        $other = CourseSeries::where('id', '!=', $series->id)->first()->lessons->first();
        try {
            app(CourseOutlineService::class)->reorder($admin, $series, [$first->id, $other->id]);
            $this->fail('Foreign lesson reorder must fail.');
        } catch (ValidationException) {
            $this->assertSame(1, $second->fresh()->position);
        }
    }

    public function test_editing_checks_with_learning_records_preserves_existing_progress(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();
        $lesson = Lesson::first();
        $progress = LessonProgress::create(['user_id' => User::factory()->create()->id, 'lesson_id' => $lesson->id, 'checks' => [true, true, true], 'progress_percent' => 100, 'completed_at' => now()]);
        Livewire::test(EditLesson::class, ['record' => $lesson->id])->fillForm(['checks' => ['新的标准']])->call('save')->assertHasFormErrors(['checks']);
        $this->assertSame(3, count($lesson->fresh()->checks));
        $this->assertSame(100, $progress->fresh()->progress_percent);
        $this->assertFalse(Gate::allows('delete', $lesson));
    }

    public function test_archived_lessons_stay_manageable_without_appearing_in_the_current_outline(): void
    {
        $admin = $this->administrator();
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $active = $series->lessons()->first();
        $archived = $active->replicate();
        $archived->fill(['slug' => 'old-outline', 'title' => '旧课时保留内容', 'position' => 9, 'status' => 'archived'])->save();

        Livewire::test(ListLessons::class)->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$archived])
            ->filterTable('status', 'archived')->assertCanSeeTableRecords([$archived]);
        Livewire::test(LessonsRelationManager::class, ['ownerRecord' => $series, 'pageClass' => EditCourseSeries::class])
            ->assertCanSeeTableRecords([$active])->assertCanNotSeeTableRecords([$archived]);
        Livewire::test(ListCourseSeries::class)->assertTableColumnStateSet('lessons_count', 1, $series);
        app(CourseOutlineService::class)->reorder($admin, $series, [$active->id]);
        $this->assertSame(9, $archived->fresh()->position);
        $this->assertSame('archived', $archived->fresh()->status);
        $this->get(route('courses.preview', $series))->assertOk()->assertDontSee('旧课时保留内容');
        $this->get(route('courses.preview', [$series, $archived->slug]))->assertOk()->assertSee('已归档');
    }

    public function test_global_theme_configuration_switches_only_registered_themes_and_can_follow_environment(): void
    {
        $this->administrator();
        Livewire::test(ThemeSettings::class)->fillForm(['theme' => 'future'])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertSee('data-theme="future"', false);
        Livewire::test(ThemeSettings::class)->fillForm(['theme' => 'pop'])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertSee('data-theme="pop"', false);
        config(['themes.active' => 'future']);
        Livewire::test(ThemeSettings::class)->fillForm(['theme' => 'environment'])->call('save')->assertHasNoFormErrors();
        $this->get('/')->assertOk()->assertSee('data-theme="future"', false);
        $this->assertDatabaseCount('theme_settings', 1);
        Livewire::test(ThemeSettings::class)->fillForm(['theme' => '../../evil'])->call('save')->assertHasFormErrors(['theme']);
        try {
            app(ThemeConfiguration::class)->save(auth()->user(), '../../evil');
            $this->fail('Unregistered themes must fail.');
        } catch (ValidationException) {
            $this->assertNull(ThemeSetting::first()->theme);
        }
        $this->actingAs(User::factory()->create());
        Livewire::test(ThemeSettings::class)->assertForbidden();
    }
}
