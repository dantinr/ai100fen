<?php

namespace App\Filament\Resources\CourseSeries\Pages;

use App\Filament\Resources\CourseSeries\CourseSeriesResource;
use App\Models\CourseSeries;
use App\Services\CourseDisplayReorderService;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ListCourseSeries extends ListRecords
{
    protected static string $resource = CourseSeriesResource::class;

    public function getTabs(): array
    {
        return [
            'courses' => Tab::make('课程')->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
            'trash' => Tab::make('回收站')->icon('heroicon-o-trash')->badge(CourseSeries::onlyTrashed()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        abort_if($this->activeTab === 'trash', 403);
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
