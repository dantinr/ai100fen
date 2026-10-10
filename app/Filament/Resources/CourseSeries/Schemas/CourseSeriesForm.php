<?php

namespace App\Filament\Resources\CourseSeries\Schemas;

use App\Models\CourseSeries;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseSeriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)->components([
                Section::make('课程内容')->schema([
                    TextInput::make('title')->label('课程名')->required()->maxLength(255)
                        ->helperText('描述要做成的真实结果，工具只是手段。')->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, string $operation, Get $get, Set $set) {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug($state ?? ''));
                            }
                        }),
                    FileUpload::make('cover')->label('课程封面')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')->directory('course-covers')->visibility('public')->maxSize(2048)
                        ->imageEditor()->imageEditorAspectRatioOptions(['16:9'])->imageAspectRatio('16:9')
                        ->automaticallyCropImagesToAspectRatio()->automaticallyResizeImagesMode('cover')
                        ->automaticallyResizeImagesToWidth('1600')->automaticallyResizeImagesToHeight('900')
                        ->automaticallyUpscaleImagesWhenResizing(false)
                        ->helperText('建议横图 1600×900（16:9），支持 JPG、PNG、WebP，最大 2 MB。'),
                    MarkdownEditor::make('description')->label('课程简介')->disableToolbarButtons(['attachFiles']),
                    Textarea::make('final_outcome')->label('课程目标')->rows(3)->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')
                        ->required(fn (Get $get) => $get('status') === 'published')
                        ->helperText('这门课程最终要做成什么？填写真实成果；Explore填写要获得的实验结论。'),
                    TagsInput::make('recommendation_keywords')->label('课程标签')->default([])
                        ->helperText('按回车添加，也作为搜索关键词及 Z 免费任务推荐依据。留空时显示主要类别。'),
                    TagsInput::make('prerequisites')->label('课前准备')->default([])
                        ->helperText('按回车逐项添加工具、账号、文件等准备事项；前置课程请在下方选择。'),
                    self::courseRelations('prerequisite_courses', '前置课程'),
                    self::courseRelations('next_courses', '后续课程'),
                ]),
                Section::make('发布与展示设置')->columns(2)->collapsible()->collapsed()->schema([
                    TextInput::make('slug')->label('课程地址标识')->required()->maxLength(150)->regex('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/')->unique(ignoreRecord: true)->helperText('英文小写、数字和短横线，例如 make-a-personal-site。'),
                    Select::make('category')->label('主要价值类别')->options(['solve' => 'Solve · 解决一个问题', 'create' => 'Create · 创作一个作品', 'explore' => 'Explore · 探索一个可能'])->required()->native(false),
                    Select::make('status')->label('课程状态')->options(['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'])->required()->default('draft')->live()
                        ->disableOptionWhen(fn (string $value, string $operation) => $operation === 'create' && $value === 'published')
                        ->helperText('课程定义完整且至少一个课时已发布，即可发布课程。归档保留内容和学习记录。'),
                    Toggle::make('is_free')->label('整门课程完整免费')->default(false)->live()->afterStateUpdated(fn (bool $state, Set $set) => $set('price', $state ? '0.00' : '100.00'))->helperText('全部已发布课时自动免费，无需逐课设置。所有当前课时发布后进入免费实验室；付费课程可单独设置免费试看。'),
                    TextInput::make('minutes')->label('预计总时长（分钟）')->numeric()->integer()->minValue(1)->maxValue(65535)->required()->default(100),
                    TextInput::make('sort_order')->label('展示排序')->numeric()->integer()->minValue(0)->maxValue(999999)->required()->default(1000)
                        ->helperText('数字越小越靠前，默认1000。控制首页、课程目录与免费实验室展示顺序，不改变课时顺序。'),
                    TextInput::make('price')->label('单课程价格（元）')->readOnly()->default('100.00')->dehydrateStateUsing(fn (Get $get) => $get('is_free') ? '0.00' : '100.00')->helperText('当前固定定价：付费100元，完整免费0元。'),
                ]),
                Section::make('真实成果与课程宪章')->collapsible()->collapsed()->description('发布前六项定义必须齐全；Explore以实验和证据结论验收，失败或证伪也可完成。')->schema([
                    Textarea::make('user_intent')->label('用户意图')->rows(3)->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('objectives')->label('阶段成果清单')->default([])->helperText('可选，按回车逐项添加；不替代课程目标与整体验收。'),
                    TagsInput::make('completion_criteria')->label('整个任务的完成标准')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('agent_role')->label('Agent负责什么')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('human_judgment_required')->label('人必须作出哪些判断')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                ]),
            ]);
    }

    private static function courseRelations(string $field, string $label): Repeater
    {
        return Repeater::make($field)->label($label)->defaultItems(0)->reorderable(false)->collapsible()
            // Keep item keys so service validation errors point to the visible row.
            ->mutateDehydratedStateUsing(fn (?array $state): array => $state ?? [])
            ->addActionLabel('添加'.$label)
            ->helperText($field === 'prerequisite_courses' ? '建议先完成的课程，不强制锁课。' : '完成当前任务后可以继续的课程；复用已有“下一步”关系。')
            ->deleteAction(fn (Action $action) => $action->requiresConfirmation()->modalHeading('移除这条课程关联？')
                ->modalDescription('保存后仅移除连接，课程、课时和学习记录都会保留。')->modalSubmitActionLabel('确认'))
            ->schema([
                Hidden::make('id'),
                Select::make('related_course_series_id')->label('选择课程')->required()->searchable()
                    ->options(fn (?CourseSeries $record) => CourseSeries::when($record, fn ($query) => $query->whereKeyNot($record->id))
                        ->orderBy('title')->get()->mapWithKeys(fn ($course) => [$course->id => $course->title.' · #'.$course->id.' · '.['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'][$course->status]]))
                    ->helperText('可选择草稿；前台只展示符合公开条件的课程。'),
                Textarea::make('description')->label('关联理由')->maxLength(500)->rows(2),
                TextInput::make('sort_order')->label('关系内排序')->numeric()->integer()->required()->default(1000)->minValue(0)->maxValue(999999),
            ]);
    }
}
