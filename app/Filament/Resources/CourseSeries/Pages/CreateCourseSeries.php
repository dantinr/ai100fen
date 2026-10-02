<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Support\MapsContentErrors;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCourseSeries extends CreateRecord
{
    use MapsContentErrors;

    protected static string $resource = CourseSeriesResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->persistContent(fn () => parent::handleRecordCreation($data));
    }

    protected function getRedirectUrl(): string
    {
        return CourseSeriesResource::getUrl('edit', ['record' => $this->record]);
    }
}
