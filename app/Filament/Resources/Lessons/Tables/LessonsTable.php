<?php

namespace App\Filament\Resources\Lessons\Tables;

use App\Filament\Resources\Lessons\Pages\ListLessons;
use App\Filament\Support\LessonBulkActions;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectCurrentPageOnly()
            ->columns([
                TextColumn::make('position')->label('顺序')->sortable(),
                TextColumn::make('title')->label('课时')->searchable()->description(fn ($record) => $record->goal),
                TextColumn::make('series.title')->label('所属课程')->searchable(),
                TextColumn::make('status')->label('状态')->badge()->formatStateUsing(fn (string $state) => ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$state] ?? $state),
                TextColumn::make('completion_share')->label('完成占比')->state(fn ($record) => $record->trashed() || $record->status === 'archived' ? '—' : round(100 / max(1, $record->series->lessons()->where('status', '!=', 'archived')->count()), 1).'%'),
                TextColumn::make('minutes')->label('分钟'),
                IconColumn::make('is_free')->label('免费访问')->state(fn ($record) => $record->series->is_free || $record->is_free)->boolean(),
            ])
            ->filters([
                SelectFilter::make('course_series_id')->label('所属课程')->relationship('series', 'title')->searchable()->preload()
                    ->visible(fn ($livewire) => $livewire instanceof ListLessons),
                SelectFilter::make('status')->label('状态')->options(['active' => '未归档', 'draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'])
                    ->visible(fn ($livewire) => ! ($livewire instanceof ListLessons && $livewire->activeTab === 'trash'))
                    ->default('active')->query(function ($query, array $data, $livewire) {
                        if ($livewire instanceof ListLessons && $livewire->activeTab === 'trash') {
                            return $query;
                        }
                        $value = $data['value'] ?? null;
                        if ($value === 'active') {
                            return $query->where('status', '!=', 'archived');
                        }
                        return $query->when($value, fn ($query) => $query->where('status', $value));
                    }),
            ])
            ->recordActions([
                EditAction::make()->label('编辑课时'),
            ])
            ->toolbarActions([
                LessonBulkActions::publish(),
                LessonBulkActions::trash(),
                LessonBulkActions::restore(),
            ])
            ->defaultSort('position');
    }
}
