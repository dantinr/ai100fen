<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Support\CourseDeletionActions;
use App\Filament\Support\ManagesCourseFormRelations;
use App\Filament\Support\MapsContentErrors;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class EditCourseSeries extends EditRecord
{
    use ManagesCourseFormRelations;
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
        return $this->persistCourseForm($data, fn (array $attributes) => parent::handleRecordUpdate($record, $attributes));
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, ...$this->courseRelationFormData($this->getRecord())];
    }

    protected function afterSave(): void
    {
        $this->form->fillPartially($this->courseRelationFormData($this->getRecord()), ['prerequisite_courses', 'next_courses']);
    }
}
