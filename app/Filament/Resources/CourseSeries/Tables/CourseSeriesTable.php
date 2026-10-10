<?php

namespace App\Filament\Resources\CourseSeries\Tables;

use App\Filament\Resources\Lessons\LessonResource;
use App\Filament\Support\CourseDeletionActions;
use App\Models\CourseSeries;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class CourseSeriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->selectCurrentPageOnly()
            ->reorderable('sort_order', fn ($livewire) => $livewire->activeTab !== 'trash')
            ->authorizeReorder(fn () => Filament::auth()->user()?->is_admin === true)
            ->reorderRecordsTriggerAction(fn (Action $action, bool $isReordering) => $action->tooltip($isReordering ? '完成排序' : '拖动调整课程展示顺序'))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('sort_order')->label('展示排序')->sortable()->visible(fn ($livewire) => $livewire->activeTab !== 'trash'),
                ImageColumn::make('cover')->label('封面')->disk('public')->imageWidth(72)->imageHeight(40)->visible(fn ($livewire) => $livewire->activeTab !== 'trash'),
                TextColumn::make('title')->label('课程')->searchable()->sortable()->description(fn ($record) => $record->slug),
                TextColumn::make('category')->label('价值路径')->badge()->formatStateUsing(fn (string $state) => ['solve' => 'Solve · 解决', 'create' => 'Create · 创作', 'explore' => 'Explore · 探索'][$state] ?? $state),
                TextColumn::make('status')->label('状态')->badge()->formatStateUsing(fn (string $state) => ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$state] ?? $state),
                IconColumn::make('is_free')->label('完整免费')->boolean()->visible(fn ($livewire) => $livewire->activeTab !== 'trash'),
                TextColumn::make('lessons_count')->label('课时')->counts(['lessons' => fn ($query) => $query->where('status', '!=', 'archived')]),
                TextColumn::make('minutes')->label('分钟')->sortable()->visible(fn ($livewire) => $livewire->activeTab !== 'trash'),
                TextColumn::make('updated_at')->label('最近修改')->dateTime('Y-m-d H:i')->timezone('Asia/Shanghai')->sortable()
                    ->visible(fn ($livewire) => $livewire->activeTab !== 'trash'),
                TextColumn::make('deleted_at')->label('删除时间')->dateTime('Y-m-d H:i')->timezone('Asia/Shanghai')
                    ->visible(fn ($livewire) => $livewire->activeTab === 'trash'),
            ])
            ->filters([
                SelectFilter::make('category')->label('价值路径')->options(['solve' => 'Solve', 'create' => 'Create', 'explore' => 'Explore']),
                SelectFilter::make('status')->label('状态')->options(['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档']),
            ])
            ->recordActions([
                Action::make('displayOrder')->label('设置排序')->icon('heroicon-o-bars-arrow-up')
                    ->authorize('update')->modalHeading('设置课程展示排序')->modalSubmitActionLabel('保存排序')
                    ->fillForm(fn (CourseSeries $record) => ['sort_order' => $record->sort_order])
                    ->schema([
                        TextInput::make('sort_order')->label('展示排序')->numeric()->integer()
                            ->minValue(0)->maxValue(999999)->required()
                            ->helperText('数字越小越靠前，默认1000。影响首页、课程目录和免费实验室；不改变课时顺序。'),
                    ])
                    ->action(function (CourseSeries $record, array $data): void {
                        Gate::authorize('update', $record);
                        $record->update(['sort_order' => $data['sort_order']]);
                        Notification::make()->title('展示排序已保存')->body('刷新前台页面即可查看新顺序。')->success()->send();
                    }),
                EditAction::make()->label('编辑与大纲'),
                Action::make('manageLessons')->label('课时管理')
                    ->visible(fn (CourseSeries $record) => ! $record->trashed())
                    ->url(fn ($record) => LessonResource::getUrl('index', ['filters' => ['course_series_id' => ['value' => $record->id]]])),
                Action::make('frontendPreview')->label('前台预览')->icon('heroicon-o-arrow-top-right-on-square')
                    ->visible(fn (CourseSeries $record) => ! $record->trashed())
                    ->url(fn ($record) => route('courses.preview', $record))->openUrlInNewTab(),
                CourseDeletionActions::trash(),
                CourseDeletionActions::restore(),
                CourseDeletionActions::permanentlyDelete(),
            ])
            ->toolbarActions([
                CourseDeletionActions::permanentlyDeleteMany(),
            ])
            ->defaultSort('sort_order');
    }
}
