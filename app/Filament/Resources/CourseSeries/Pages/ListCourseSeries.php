<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Services\CourseDisplayReorderService;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Validation\ValidationException;

class ListCourseSeries extends ListRecords
{
    protected static string $resource = CourseSeriesResource::class;

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        try {
            app(CourseDisplayReorderService::class)->reorder(Filament::auth()->user(), $order);
        } catch (ValidationException $exception) {
            Notification::make()->title('排序未保存')->body($exception->errors()['order'][0] ?? '请检查课程顺序后重试。')->danger()->send();

            return;
        }

        $this->resetTable();
        Notification::make()->title('课程展示顺序已保存')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
