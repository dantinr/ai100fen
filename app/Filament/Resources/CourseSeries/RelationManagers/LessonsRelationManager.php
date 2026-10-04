<?php

namespace App\Filament\Resources\CourseSeries\RelationManagers;

use App\Filament\Resources\Lessons\LessonResource;
use App\Services\CourseOutlineService;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class LessonsRelationManager extends RelationManager
{
    protected static string $relationship = 'lessons';

    protected static bool $isLazy = false;

    protected static ?string $relatedResource = LessonResource::class;

    protected static ?string $title = '课程大纲 · 课时与目标';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->where('status', '!=', 'archived'))
            ->reorderable('position')
            ->headerActions([
                CreateAction::make()->label('添加课时')->url(fn () => LessonResource::getUrl('create', ['series' => $this->getOwnerRecord()->id])),
            ]);
    }

    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        app(CourseOutlineService::class)->reorder(Filament::auth()->user(), $this->getOwnerRecord(), $order);
        $this->resetTable();
        $this->dispatch('course-outline-updated');
        Notification::make()->title('大纲顺序已保存')->body('请核对分值和最终验收，课程草稿需重新发布。')->success()->send();
    }
}
