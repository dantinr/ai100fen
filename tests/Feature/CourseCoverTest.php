<?php

namespace Tests\Feature;

use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Models\CourseSeries;
use App\Models\User;
use App\Services\FreeLabInstaller;
use App\Services\LegacyCourseImporter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CourseCoverTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_upload_a_cover_used_by_legacy_catalog_pages(): void
    {
        Storage::fake('public');
        app(LegacyCourseImporter::class)->run();
        $series = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $this->get('/series/build-a-website')->assertOk()->assertSee('data-course-goal', false);

        $this->administrator();
        Livewire::test(ListCourseSeries::class)->assertTableColumnExists('cover');
        Livewire::test(EditCourseSeries::class, ['record' => $series->slug])
            ->set('data.cover', UploadedFile::fake()->image('cover.jpg', 1600, 900))
            ->call('save')->assertHasNoFormErrors();

        $path = $series->fresh()->cover;
        $this->assertNotNull($path);
        $this->assertStringStartsWith('course-covers/', $path);
        Storage::disk('public')->assertExists($path);
        $url = Storage::disk('public')->url($path);
        $this->get('/')->assertOk()->assertSee($url);
        $this->get('/series')->assertOk()->assertSee($url);
        $this->get('/series/build-a-website')->assertOk()->assertSee('data-course-goal', false)->assertDontSee($url);

        Storage::disk('public')->delete($path);
        $this->get('/series/build-a-website')->assertOk()->assertSee('data-course-goal', false);
    }

    public function test_cover_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        app(LegacyCourseImporter::class)->run();
        $series = CourseSeries::where('slug', 'build-a-website')->firstOrFail();
        $this->administrator();

        Livewire::test(EditCourseSeries::class, ['record' => $series->slug])
            ->set('data.cover', UploadedFile::fake()->create('notes.txt', 1, 'text/plain'))
            ->call('save')->assertHasFormErrors(['cover']);
        Livewire::test(EditCourseSeries::class, ['record' => $series->slug])
            ->set('data.cover', UploadedFile::fake()->image('large.jpg')->size(3072))
            ->call('save')->assertHasFormErrors(['cover']);
        $this->assertNull($series->fresh()->cover);
    }

    public function test_free_task_list_and_public_course_page_show_saved_cover(): void
    {
        Storage::fake('public');
        app(FreeLabInstaller::class)->install();
        $series = CourseSeries::where('is_free', true)->firstOrFail();
        $path = UploadedFile::fake()->image('example.jpg', 1600, 900)->store('course-covers', 'public');
        $series->update(['cover' => $path]);
        $url = Storage::disk('public')->url($path);

        $this->get('/lab')->assertOk()->assertSee($url)->assertSee('free-card-cover', false);
        $this->get('/courses/'.$series->slug)->assertOk()->assertSee($url)->assertSee('course-overview-cover', false);
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
