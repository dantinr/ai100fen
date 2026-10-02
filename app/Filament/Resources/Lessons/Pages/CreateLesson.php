<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Support\MapsContentErrors;
use App\Models\CourseSeries;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateLesson extends CreateRecord
{
    use MapsContentErrors;

    protected static string $resource = LessonResource::class;

    protected function afterFill(): void
    {
        $seriesId = filter_var(request()->query('series'), FILTER_VALIDATE_INT);
        if ($seriesId && ($series = CourseSeries::find($seriesId))) {
            $this->data['course_series_id'] = $series->id;
            $this->data['position'] = (int) $series->lessons()->max('position') + 1;
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->persistContent(fn () => parent::handleRecordCreation($data));
    }

    protected function getRedirectUrl(): string
    {
        return CourseSeriesResource::getUrl('edit', ['record' => $this->record->series]);
    }
}
