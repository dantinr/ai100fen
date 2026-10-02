<?php

namespace App\Filament\Resources\CourseSeries\RelationManagers;

use App\Models\CourseRelation;
use App\Models\CourseSeries;
use App\Services\CourseRelationService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CourseRelationsRelationManager extends RelationManager
{
    protected static string $relationship = 'courseRelations';

    protected static ?string $title = '课程关系';

    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('relation_type')->label('关系类型')->options(CourseRelation::TYPES)->required()
                ->helperText('前置：建议先完成所选课程；推荐：相关任务；下一步：完成当前任务后可以继续。均为单向关系。'),
            Select::make('related_course_series_id')->label('关联课程')->searchable()->required()
                ->options(fn () => CourseSeries::whereKeyNot($this->getOwnerRecord()->id)->orderBy('title')->get()
                    ->mapWithKeys(fn ($course) => [$course->id => $course->title.' · #'.$course->id.' · '.['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$course->status]]))
                ->helperText('可以编排草稿；前台只展示已发布且有公开入口的课程。'),
            TextInput::make('sort_order')->label('关系内排序')->numeric()->integer()->minValue(0)->maxValue(999999)->required()->default(1000)
                ->helperText('同一关系类型内数字越小越靠前，不改变首页展示排序或课时顺序。'),
            Textarea::make('description')->label('关联理由')->maxLength(500)->rows(3)
                ->helperText('向用户说明为什么建议学习这门课程，可留空。'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('description')
            ->description('前置仅为建议，不锁定课程。移除关联保留课程和学习记录。')
            ->modifyQueryUsing(fn ($query) => $query->with('relatedCourse'))
            ->columns([
                TextColumn::make('relatedCourse.title')->label('关联课程')->searchable()->description(fn ($record) => '#'.$record->related_course_series_id),
                TextColumn::make('relatedCourse.status')->label('目标状态')->badge()
                    ->formatStateUsing(fn ($state) => ['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$state]),
                TextColumn::make('sort_order')->label('关系内排序')->sortable(),
                TextColumn::make('description')->label('关联理由')->wrap()->limit(100),
            ])
            ->defaultGroup(Group::make('relation_type')->label('关系类型')->titlePrefixedWithLabel(false)
                ->getTitleFromRecordUsing(fn ($record) => CourseRelation::TYPES[$record->relation_type]))
            ->filters([SelectFilter::make('relation_type')->label('关系类型')->options(CourseRelation::TYPES)])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()->label('添加课程关联')->modalHeading('添加课程关联')->createAnother(false)
                    ->using(fn (array $data) => $this->saveRelation($data)),
            ])
            ->recordActions([
                EditAction::make()->label('编辑关联')->modalHeading('编辑课程关联')
                    ->using(fn (CourseRelation $record, array $data) => $this->saveRelation($data, $record)),
                DeleteAction::make()->label('移除关联')->modalHeading('移除这条课程关联？')
                    ->modalDescription('只移除连接，课程、课时和学习记录会保留。')->modalSubmitActionLabel('移除关联')
                    ->successNotificationTitle('关联已移除')
                    ->using(fn (CourseRelation $record) => app(CourseRelationService::class)->remove(Filament::auth()->user(), $this->getOwnerRecord(), $record)),
            ]);
    }

    private function saveRelation(array $data, ?CourseRelation $record = null): Model
    {
        try {
            return app(CourseRelationService::class)->save(Filament::auth()->user(), $this->getOwnerRecord(), $data, $record);
        } catch (ValidationException $exception) {
            $errors = [];
            foreach ($exception->errors() as $field => $messages) {
                $errors[$this->getMountedActionSchema()->getStatePath().'.'.$field] = $messages;
            }
            throw ValidationException::withMessages($errors);
        }
    }
}
