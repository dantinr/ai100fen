<?php

namespace Tests\Feature;

use App\Models\CourseSeries;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\CourseAccessService;
use App\Services\FreeLabInstaller;
use App\Services\LegacyCourseImporter;
use App\Support\FrontendCatalog;
use App\Support\WebsiteFirstLessonContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyCourseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_write_courses_or_lessons(): void
    {
        $this->artisan('courses:import-legacy --dry-run')->assertSuccessful();
        $this->assertDatabaseCount('course_series', 0);
        $this->assertDatabaseCount('lessons', 0);
    }

    public function test_import_preserves_the_free_lab_and_migrates_only_existing_content_as_drafts(): void
    {
        app(FreeLabInstaller::class)->install();
        $freeBefore = CourseSeries::freeLab()->get()->toArray();
        $report = app(LegacyCourseImporter::class)->run();
        $this->assertSame(16, $report['created']);
        $this->assertSame(5, $report['lessons']);
        $this->assertDatabaseCount('course_series', 19);
        $this->assertDatabaseCount('lessons', 8);
        $this->assertSame($freeBefore, CourseSeries::freeLab()->get()->toArray());
        $sources = app(FrontendCatalog::class)->all();
        foreach ($sources as $source) {
            $series = CourseSeries::where('slug', $source['slug'])->firstOrFail();
            $this->assertSame('draft', $series->status);
            $this->assertSame('100.00', $series->price);
            $this->assertFalse($series->is_free);
            $this->assertSame($source['question'], $series->user_intent);
            $this->assertSame($source['outcome'], $series->final_outcome);
            foreach (['completion_criteria', 'agent_role', 'human_judgment_required'] as $field) {
                $this->assertNotEmpty($series->$field);
            }
            foreach ($source['prerequisites'] as $prerequisite) {
                $this->assertStringContainsString($prerequisite, $series->description);
            }
            $this->assertSame(count($source['lessons']), $series->lessons()->count());
        }
        // These choices intentionally differ from mechanical work→solve / build→create mapping.
        $this->assertSame('create', CourseSeries::where('slug', 'create-a-presentation')->first()->category);
        $this->assertSame('create', CourseSeries::where('slug', 'write-a-research-report')->first()->category);
        $this->assertSame('solve', CourseSeries::where('slug', 'add-website-support')->first()->category);
        $website = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $lessons = $website->lessons()->get();
        $this->assertSame(range(20, 100, 20), $lessons->pluck('score')->all());
        $this->assertSame(100, $lessons->sum('points'));
        $this->assertSame(4, $lessons->where('status', 'draft')->count());
        $this->assertSame('published', $lessons->first()->status);
        $content = app(FrontendCatalog::class)->previewContent($sources[0], $sources[0]['lessons'][0]);
        $this->assertSame($content['prompt'], $lessons->first()->prompt);
        $this->assertSame($content['content'], $lessons->first()->content);
        $this->assertSame($content['code'], $lessons->first()->code);
        $this->assertSame($content['checks'], $lessons->first()->checks);
        $this->assertStringContainsString($content['checks'][0], $lessons->first()->resources[0]['content']);
        $this->assertFalse(app(CourseAccessService::class)->canAccess($website, $lessons->first()));
        // A persisted draft cannot fall back to the independently published static preview.
        $this->get('/series/build-a-website/lessons/server-and-ip')->assertNotFound();
        $this->get('/lab')->assertOk()->assertDontSee($website->title);
    }

    public function test_repeated_import_preserves_edited_courses_lessons_and_learning_records(): void
    {
        app(LegacyCourseImporter::class)->run();
        $series = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $series->update(['title' => '管理员修改后的课程名称', 'description' => '自己的课程介绍']);
        $lesson = $series->lessons()->first();
        $lesson->update(['intro' => '管理员修改后的简介']);
        $progress = LessonProgress::create(['user_id' => User::factory()->create()->id, 'lesson_id' => $lesson->id, 'checks' => [true, false, false], 'progress_percent' => 33]);
        $before = [CourseSeries::all()->toArray(), Lesson::all()->toArray(), $progress->fresh()->toArray()];
        $report = app(LegacyCourseImporter::class)->run();
        $this->assertSame(0, $report['created']);
        $this->assertSame(0, $report['lessons']);
        $this->assertSame(16, $report['skipped']);
        $this->assertSame($before, [CourseSeries::all()->toArray(), Lesson::all()->toArray(), $progress->fresh()->toArray()]);
    }

    public function test_first_lesson_content_sync_requires_original_version_and_preserves_progress(): void
    {
        app(LegacyCourseImporter::class)->run();
        $lesson = CourseSeries::where('slug', 'build-a-website')->firstOrFail()->lessons()->firstOrFail();
        $original = WebsiteFirstLessonContent::original();
        $lesson->update([
            'goal' => $original['goal'], 'intro' => $original['intro'],
            'objectives' => [$original['goal']], 'content' => null,
            'steps' => array_map(fn (array $step) => ['body' => $step['body'], 'title' => $step['title']], $original['steps']),
            'prompt' => $original['prompt'],
            'checks' => $original['checks'],
            'code' => $original['code'],
        ]);
        $progress = LessonProgress::create([
            'user_id' => User::factory()->create()->id, 'lesson_id' => $lesson->id,
            'checks' => [true, false, false], 'progress_percent' => 33,
        ]);

        $this->artisan('courses:sync-website-first-lesson')->assertSuccessful();
        $this->assertNull($lesson->fresh()->content);
        $this->artisan('courses:sync-website-first-lesson --apply')->assertSuccessful();
        $fresh = $lesson->fresh();
        $this->assertSame('published', $fresh->status);
        $this->assertSame($original['checks'], $fresh->checks);
        $this->assertSame(20, $fresh->points);
        $this->assertSame($original['code'], $fresh->code);
        $this->assertSame(33, $progress->fresh()->progress_percent);
        $this->assertStringContainsString('连接超时', $fresh->content);
        $this->artisan('courses:sync-website-first-lesson --apply')->assertSuccessful();

        $fresh->update(['intro' => '管理员后续修改']);
        $this->artisan('courses:sync-website-first-lesson --apply')->assertFailed();
        $this->assertSame('管理员后续修改', $lesson->fresh()->intro);
    }
}
