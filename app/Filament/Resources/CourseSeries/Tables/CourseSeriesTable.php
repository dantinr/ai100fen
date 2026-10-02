<?php

namespace App\Filament\Resources\CourseSeries\Tables;

use App\Filament\Resources\Lessons\LessonResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CourseSeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->label('课程')->searchable()->sortable()->description(fn ($record) => $record->slug),
                TextColumn::make('category')->label('价值路径')->badge()->formatStateUsing(fn (string $state) => ['solve' => 'Solve · 解决', 'create' => 'Create · 创作', 'explore' => 'Explore · 探索'][$state] ?? $state),
                TextColumn::make('status')->label('状态')->badge()->formatStateUsing(fn (string $state) => ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$state] ?? $state),
                IconColumn::make('is_free')->label('完整免费')->boolean(),
                TextColumn::make('lessons_count')->label('课时')->counts('lessons'),
                TextColumn::make('minutes')->label('分钟')->sortable(),
                TextColumn::make('updated_at')->label('最近修改')->dateTime('Y-m-d H:i')->timezone('Asia/Shanghai')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->label('价值路径')->options(['solve' => 'Solve', 'create' => 'Create', 'explore' => 'Explore']),
                SelectFilter::make('status')->label('状态')->options(['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档']),
            ])
            ->recordActions([
                EditAction::make()->label('编辑与大纲'),
                Action::make('manageLessons')->label('课时管理')
                    ->url(fn ($record) => LessonResource::getUrl('index', ['filters' => ['course_series_id' => ['value' => $record->id]]])),
                Action::make('frontendPreview')->label('前台预览')->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => route('courses.preview', $record))->openUrlInNewTab(),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
