<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Support\MapsContentErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class EditCourseSeries extends EditRecord
{
    use MapsContentErrors;

    protected static string $resource = CourseSeriesResource::class;

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
