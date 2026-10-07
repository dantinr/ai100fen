<?php

namespace App\Filament\Support;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Services\LessonBulkActionService;
use Filament\Actions\BulkAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class LessonBulkActions
{
    public static function publish(): BulkAction
    {
        return BulkAction::make('publish')->label('批量发布')->icon('heroicon-o-check-circle')
            ->authorize(fn ($livewire) => $livewire instanceof ListLessons && Filament::auth()->user()?->is_admin === true)
            ->visible(fn ($livewire) => $livewire instanceof ListLessons && $livewire->activeTab !== 'trash')
            ->requiresConfirmation()->modalHeading('发布所选课时？')
            ->modalDescription('逐课校验目标、步骤、Prompt和验收标准；任一课时不合格时整批不改动。课时状态变化会使所属课程退回草稿，请在课程管理审核后重新发布。')
            ->modalSubmitActionLabel('确认发布')->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records, BulkAction $action, $livewire) => self::run($records, $action, $livewire, 'publish', '课时已发布'));
    }

    public static function trash(): BulkAction
    {
        return BulkAction::make('delete')->label('批量删除')->icon('heroicon-o-trash')->color('danger')
            ->authorize(fn ($livewire) => $livewire instanceof ListLessons && Filament::auth()->user()?->is_admin === true)
            ->visible(fn ($livewire) => $livewire instanceof ListLessons && $livewire->activeTab !== 'trash')
            ->requiresConfirmation()->modalHeading('删除所选课时？')
            ->modalDescription('所选课时将移入课时回收站，保留内容和上传文件，可恢复为草稿。已有学习记录的课时不能删除，任一课时被拦截时整批不改动；所属课程退回草稿，需要重新审核发布。')
            ->modalSubmitActionLabel('移入回收站')->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records, BulkAction $action, $livewire) => self::run($records, $action, $livewire, 'trash', '课时已移入回收站'));
    }

    public static function restore(): BulkAction
    {
        return BulkAction::make('restore')->label('批量恢复')->icon('heroicon-o-arrow-uturn-left')
            ->authorize(fn ($livewire) => $livewire instanceof ListLessons && Filament::auth()->user()?->is_admin === true)
            ->visible(fn ($livewire) => $livewire instanceof ListLessons && $livewire->activeTab === 'trash')
            ->requiresConfirmation()->modalHeading('恢复所选课时？')
            ->modalDescription('课时与所属课程均恢复为草稿，审核后分别发布；不会自动开放前台访问。')
            ->modalSubmitActionLabel('恢复为草稿')->deselectRecordsAfterCompletion()
            ->action(fn (Collection $records, BulkAction $action, $livewire) => self::run($records, $action, $livewire, 'restore', '课时已恢复为草稿'));
    }

    private static function run(Collection $records, BulkAction $action, ListLessons $livewire, string $operation, string $title): void
    {
        try {
            $selected = array_map('strval', $livewire->selectedTableRecords);
            $resolved = array_map('strval', $records->modelKeys());
            sort($selected);
            sort($resolved);
            if ($livewire->isTrackingDeselectedTableRecords || $selected !== $resolved) {
                throw ValidationException::withMessages(['lessons' => '所选课时已变化，请刷新列表后重新选择；整批未改动。']);
            }
            $count = app(LessonBulkActionService::class)->$operation(Filament::auth()->user(), $livewire->selectedTableRecords);
        } catch (ValidationException $exception) {
            Notification::make()->title('批量操作未完成')->body(e(implode('；', $exception->validator->errors()->all())))->danger()->send();
            $action->halt();
        }
        Notification::make()->title($title)->body('共'.$count.'个课时。请核对课程大纲并重新审核发布。')->success()->send();
    }
}
