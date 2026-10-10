<?php

namespace App\Filament\Support;

use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Models\CourseSeries;
use App\Services\CourseDeletionService;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CourseDeletionActions
{
    public static function trash(): DeleteAction
    {
        return DeleteAction::make()->label('删除课程')->authorize('delete')
            ->modalHeading(fn (CourseSeries $record) => '删除课程“'.$record->title.'”？')
            ->modalDescription('课程将进入回收站并停止前台访问。课时、学习记录和课程关系保留，可在回收站恢复。')
            ->modalSubmitActionLabel('移入回收站')->successNotificationTitle('课程已移入回收站')
            ->using(function (CourseSeries $record): bool {
                app(CourseDeletionService::class)->trash(Filament::auth()->user(), $record);

                return true;
            });
    }

    public static function restore(): RestoreAction
    {
        return RestoreAction::make()->label('恢复课程')->authorize('restore')
            ->modalHeading('恢复这门课程？')->modalDescription('课程、课时及原学习记录将恢复可管理状态。课程恢复为草稿，审核发布后才能重新公开。')
            ->modalSubmitActionLabel('恢复为草稿')->successNotificationTitle('课程已恢复为草稿')
            ->using(function (CourseSeries $record): bool {
                app(CourseDeletionService::class)->restore(Filament::auth()->user(), $record);

                return true;
            });
    }

    public static function permanentlyDelete(): ForceDeleteAction
    {
        return ForceDeleteAction::make()->label('最终删除')->authorize('forceDelete')
            ->modalHeading(fn (CourseSeries $record) => '最终删除“'.$record->title.'”？')
            ->modalDescription('将永久删除课程、全部课时和相关课程连接，无法恢复。有学习记录的课程禁止最终删除；上传封面文件保留。')
            ->modalSubmitActionLabel('确认')->successNotificationTitle('课程已最终删除')
            ->using(function (CourseSeries $record, ForceDeleteAction $action): bool {
                try {
                    app(CourseDeletionService::class)->permanentlyDelete(Filament::auth()->user(), $record);
                } catch (ValidationException $exception) {
                    Notification::make()->title('课程未删除')->body(e(implode('；', $exception->validator->errors()->all())))->danger()->send();
                    $action->halt();
                }

                return true;
            });
    }

    public static function permanentlyDeleteMany(): BulkAction
    {
        return BulkAction::make('forceDelete')->label('批量最终删除')->icon('heroicon-o-trash')->color('danger')
            ->authorize(fn ($livewire) => $livewire instanceof ListCourseSeries && $livewire->activeTab === 'trash' && Filament::auth()->user()?->is_admin === true)
            ->visible(fn ($livewire) => $livewire instanceof ListCourseSeries && $livewire->activeTab === 'trash')
            ->requiresConfirmation()
            ->modalHeading(fn (Collection $records) => '最终删除所选'.$records->count().'门课程？')
            ->modalDescription('将永久删除所选课程、全部课时和课程连接，无法恢复；上传文件保留。任一课程已有学习记录或选择失效时，整批不删除。')
            ->modalSubmitActionLabel('确认')
            ->action(function (Collection $records, BulkAction $action, ListCourseSeries $livewire): void {
                try {
                    $selected = array_map('strval', $livewire->selectedTableRecords);
                    $resolved = array_map('strval', $records->modelKeys());
                    sort($selected);
                    sort($resolved);
                    if ($livewire->isTrackingDeselectedTableRecords || $selected !== $resolved) {
                        throw ValidationException::withMessages(['confirmation' => '所选课程已变化，请刷新回收站后重新选择；整批未删除。']);
                    }
                    $count = app(CourseDeletionService::class)->permanentlyDeleteMany(Filament::auth()->user(), $livewire->selectedTableRecords);
                } catch (ValidationException $exception) {
                    Notification::make()->title('批量删除未完成')->body(e(implode('；', $exception->validator->errors()->all())))->danger()->send();
                    $action->halt();
                }
                Notification::make()->title('所选课程已最终删除')->body('共'.$count.'门课程，上传文件保留。')->success()->send();
                $livewire->deselectAllTableRecords();
                $action->success();
            });
    }
}
