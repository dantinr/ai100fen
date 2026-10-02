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
        $this->get('/free')->assertDontSee($series->title);
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
        $this->get('/free/admin-task-page/make-and-check')->assertOk()->assertSee('生成真实成品');
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

    public function test_course_and_lesson_editors_render_existing_arrays_and_save_markdown_safely(): void
    {
        $this->administrator();
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::first();
        $lesson = $series->lessons->first();
        $lessonListUrl = LessonResource::getUrl('index', ['filters' => ['course_series_id' => ['value' => $series->id]]]);
        Livewire::test(ListCourseSeries::class)->assertTableColumnExists('id')->assertTableActionHasUrl('manageLessons', $lessonListUrl, $series);
        Livewire::withQueryParams(['filters' => ['course_series_id' => ['value' => $series->id]]])->test(ListLessons::class)
            ->assertCanSeeTableRecords([$lesson])
            ->assertCanNotSeeTableRecords(Lesson::where('course_series_id', '!=', $series->id)->get());
        $this->get('/galaxy/course-series/'.$series->slug.'/edit')->assertOk()->assertSee('课程大纲');
        $this->get('/galaxy/lessons/'.$lesson->id.'/edit')->assertOk()->assertSee('课时大纲');
        Livewire::test(EditLesson::class, ['record' => $lesson->id])->fillForm([
            'objectives' => ['完成真实网页'], 'content' => "## 正文目标\n\n<script>evil()</script>\n\n[危险](javascript:alert(1))", 'video_url' => 'https://example.test/video',
        ])->call('save')->assertHasNoFormErrors();
        $this->get('/free/'.$series->slug.'/'.$lesson->slug)->assertOk()->assertSee('正文目标')->assertSee('完成真实网页')->assertDontSee('<script>evil()</script>', false)->assertDontSee('href="javascript:', false);
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
        $this->get('/free/'.$series->slug.'/'.$first->slug)->assertNotFound();
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
