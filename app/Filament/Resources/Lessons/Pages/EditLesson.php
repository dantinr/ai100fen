<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Support\MapsContentErrors;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditLesson extends EditRecord
{
    use MapsContentErrors;

    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('course')->label('返回课程大纲')->url(fn () => CourseSeriesResource::getUrl('edit', ['record' => $this->record->series])),
        ];
    }

    public function areFormActionsSticky(): bool
    {
        return true;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->persistContent(fn () => parent::handleRecordUpdate($record, $data));
    }
}
