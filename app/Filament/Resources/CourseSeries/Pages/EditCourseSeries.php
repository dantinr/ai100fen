<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Support\MapsContentErrors;
use App\Filament\Support\CourseDeletionActions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class EditCourseSeries extends EditRecord
{
    use MapsContentErrors;

    protected static string $resource = CourseSeriesResource::class;

    protected function getHeaderActions(): array
    {
        return [CourseDeletionActions::trash()->successRedirectUrl(CourseSeriesResource::getUrl('index'))];
    }

    public function areFormActionsSticky(): bool
    {
        return true;
    }

    #[On('course-outline-updated')]
    public function refreshPublicationState(): void
    {
        $this->authorizeAccess();
        $this->record->refresh();
        $this->data['status'] = $this->record->status;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->persistContent(fn () => parent::handleRecordUpdate($record, $data));
    }
}
