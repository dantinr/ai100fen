<?php

namespace Tests\Feature;

use App\Filament\Resources\Lessons\Pages\EditLesson;
use App\Models\CourseSeries;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\FreeLabInstaller;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LessonVideoPosterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        app(FreeLabInstaller::class)->install();
        config(['player.demo_enabled' => false]);
    }

    public function test_admin_can_upload_replace_and_remove_poster_without_changing_learning_records(): void
    {
        $course = CourseSeries::where('slug', 'personal-intro-page')->firstOrFail();
        $lesson = $course->lessons()->firstOrFail();
        $user = User::factory()->create();
        $progress = LessonProgress::create(['user_id' => $user->id, 'lesson_id' => $lesson->id,
            'checks' => [true, true, true], 'progress_percent' => 100, 'completed_at' => now()]);
        $before = $progress->refresh()->getAttributes();
        $this->administrator();

        Livewire::test(EditLesson::class, ['record' => $lesson->id])
            ->set('data.video_poster', UploadedFile::fake()->image('poster.jpg', 1600, 900))
            ->call('save')->assertHasNoFormErrors();
        $first = $lesson->fresh()->video_poster;
        $this->assertStringStartsWith('lesson-video-posters/', $first);
        Storage::disk('public')->assertExists($first);

        Livewire::test(EditLesson::class, ['record' => $lesson->id])
            ->set('data.video_poster', [])
            ->set('data.video_poster', UploadedFile::fake()->image('replacement.png', 1600, 900))
            ->call('save')->assertHasNoFormErrors();
        $second = $lesson->fresh()->video_poster;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertExists($first);
        Storage::disk('public')->assertExists($second);

        Livewire::test(EditLesson::class, ['record' => $lesson->id])
            ->set('data.video_poster', [])->call('save')->assertHasNoFormErrors();
        $this->assertNull($lesson->fresh()->video_poster);
        Storage::disk('public')->assertExists($second);
        $this->assertSame($before, $progress->fresh()->getAttributes());
        $this->assertSame('published', $lesson->fresh()->status);
        $this->assertSame('published', $course->fresh()->status);
    }

    public function test_poster_upload_rejects_non_images_oversized_images_and_unsafe_paths(): void
    {
        $lesson = CourseSeries::first()->lessons()->firstOrFail();
        $this->administrator();
        foreach ([UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
            UploadedFile::fake()->image('large.jpg')->size(3072),
            UploadedFile::fake()->create('poster.svg', 1, 'image/svg+xml')] as $file) {
            Livewire::test(EditLesson::class, ['record' => $lesson->id])
                ->set('data.video_poster', $file)->call('save')->assertHasFormErrors(['video_poster']);
        }
        foreach (['../private/secret.png', 'lesson-video-posters/../secret.jpg', 'https://example.com/poster.jpg', 'lesson-video-posters/unsafe.svg'] as $path) {
            try {
                $lesson->fresh()->update(['video_poster' => $path]);
                $this->fail('Unsafe poster path was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('video_poster', $exception->errors());
            }
        }
        $this->assertNull($lesson->fresh()->video_poster);
    }

    public function test_poster_precedence_fallback_and_admin_preview_respect_lesson_access(): void
    {
        $course = CourseSeries::where('slug', 'personal-intro-page')->firstOrFail();
        $lesson = $course->lessons()->firstOrFail();
        $poster = UploadedFile::fake()->image('poster.jpg')->store('lesson-video-posters', 'public');
        $cover = UploadedFile::fake()->image('cover.jpg')->store('course-covers', 'public');
        $course->update(['cover' => $cover]);
        $lesson->update(['video_poster' => $poster, 'video_url' => 'https://media.example.com/lesson.m3u8']);
        $posterUrl = Storage::disk('public')->url($poster);
        $coverUrl = Storage::disk('public')->url($cover);
        $path = route('free.lesson', [$course, $lesson->slug]);

        $this->get($path)->assertOk()->assertSee($posterUrl)->assertDontSee($coverUrl);
        $this->get(route('courses.show', $course))->assertOk()->assertDontSee($posterUrl);
        $this->administrator();
        $this->get(route('courses.preview', [$course, $lesson->slug]))->assertOk()->assertSee($posterUrl);
        Storage::disk('public')->delete($poster);
        $this->get($path)->assertOk()->assertSee($coverUrl)->assertDontSee($posterUrl);
        $lesson->update(['video_poster' => null]);
        $this->get($path)->assertOk()->assertSee($coverUrl);
        $course->update(['is_free' => false, 'price' => '100.00']);
        $this->actingAs(User::factory()->create())->get($path)->assertNotFound()->assertDontSee($posterUrl);
        $this->get('/galaxy/lessons/'.$lesson->id.'/edit')->assertForbidden();
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    public function test_configured_poster_also_works_with_local_demo_without_creating_progress(): void
    {
        app()->instance('env', 'local');
        config(['player.demo_enabled' => true]);
        $course = CourseSeries::first();
        $lesson = $course->lessons()->firstOrFail();
        $poster = UploadedFile::fake()->image('poster.jpg')->store('lesson-video-posters', 'public');
        $lesson->update(['video_poster' => $poster]);
        $path = route('free.lesson', [$course, $lesson->slug]);
        $this->get($path)->assertOk()->assertSee(Storage::disk('public')->url($poster))
            ->assertSee('演示视频 · 非课程录播')->assertDontSee(config('player.demo_poster'));
        $lesson->update(['video_poster' => null]);
        $this->get($path)->assertOk()->assertSee(config('player.demo_poster'));
        $this->assertDatabaseCount('lesson_progress', 0);
    }

    private function administrator(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('galaxy'));
        Filament::bootCurrentPanel();
    }
}
