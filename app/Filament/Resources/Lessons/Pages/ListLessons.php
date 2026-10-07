<?php

namespace App\Filament\Resources\Lessons\Pages;

use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLessons extends ListRecords
{
    protected static string $resource = LessonResource::class;

    public function getTabs(): array
    {
        return [
            'lessons' => Tab::make('课时')->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
            'trash' => Tab::make('课时回收站')->icon('heroicon-o-trash')->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn () => $this->activeTab !== 'trash')->url(fn () => LessonResource::getUrl('create', ['series' => $this->getTableFilterState('course_series_id')['value'] ?? null])),
        ];
    }
}
