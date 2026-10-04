<?php

namespace App\Filament\Support;

use App\Models\CourseSeries;
use App\Services\CourseDeletionService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Illuminate\Validation\Rule;
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
            ->modalSubmitActionLabel('确认最终删除')->successNotificationTitle('课程已最终删除')
            ->schema([
                TextInput::make('confirmation')->label('输入课程地址标识确认')->required()
                    ->helperText(fn (CourseSeries $record) => '请输入：'.$record->slug)
                    ->rules(fn (CourseSeries $record) => [Rule::in([$record->slug])])
                    ->validationMessages(['in' => '课程地址标识不一致，请核对后再确认。']),
            ])
            ->using(function (CourseSeries $record, array $data, $livewire): bool {
                try {
                    app(CourseDeletionService::class)->permanentlyDelete(Filament::auth()->user(), $record, $data['confirmation']);
                } catch (ValidationException $exception) {
                    throw ValidationException::withMessages([
                        $livewire->getSchema($livewire->getMountedActionSchemaName())->getStatePath().'.confirmation' => $exception->errors()['confirmation'],
                    ]);
                }

                return true;
            });
    }
}
