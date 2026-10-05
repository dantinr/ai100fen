<?php

namespace App\Filament\Resources\Lessons\Schemas;

use App\Models\Lesson;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)->components([
                Section::make('课时基本信息')->columns(2)->schema([
                    Select::make('course_series_id')->label('所属课程')->relationship('series', 'title')->searchable()->preload()->required()->disabled(fn (string $operation) => $operation === 'edit'),
                    TextInput::make('title')->label('课时名称')->required()->maxLength(255),
                    TextInput::make('slug')->label('课时地址标识')->required()->maxLength(150)->regex('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/')
                        ->rules(fn (Get $get, ?Lesson $record) => [Rule::unique('lessons', 'slug')->where('course_series_id', $get('course_series_id'))->ignore($record?->id)]),
                    Select::make('status')->label('课时状态')->options(['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'])->default('draft')->required()->live(),
                    TextInput::make('position')->label('大纲顺序')->numeric()->integer()->minValue(1)->maxValue(65535)->required()->default(1),
                    TextInput::make('minutes')->label('预计时长（分钟）')->numeric()->integer()->minValue(1)->maxValue(65535)->required()->default(10),
                    TextInput::make('score')->label('累计展示分值')->numeric()->integer()->minValue(1)->maxValue(100)->required()->default(10)->helperText('标准课程依次10、20至100；最后一课应验收整个任务。'),
                    TextInput::make('points')->label('本课验收权重')->numeric()->integer()->minValue(1)->maxValue(100)->required()->default(10)->helperText('标准课时10；单课完整免费任务100。修改大纲、状态或分值后须重新发布课程。'),
                    Toggle::make('is_free')->label('付费课程的免费试看课时')->default(false)->helperText('完整免费由课程配置决定，此选项只表示单个课时试看。'),
                ]),
                Section::make('视频播放器')->schema([
                    TextInput::make('video_url')->label('视频地址（可选）')->url()->startsWith('https://')->maxLength(2048)->helperText('填写 HTTPS 直连 HLS / MP4 媒体地址，不是视频网站观看页。'),
                    FileUpload::make('video_poster')->label('播放器封面（可选）')->image()->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->disk('public')->directory('lesson-video-posters')->visibility('public')->maxSize(2048)
                        ->imageEditor()->imageEditorAspectRatioOptions(['16:9'])->imageAspectRatio('16:9')
                        ->automaticallyCropImagesToAspectRatio()->automaticallyResizeImagesMode('cover')
                        ->automaticallyResizeImagesToWidth('1600')->automaticallyResizeImagesToHeight('900')
                        ->automaticallyUpscaleImagesWhenResizing(false)
                        ->helperText('播放前展示。建议横图 1600×900（16:9），支持 JPG、PNG、WebP，最大 2 MB。未设置时使用课程封面；更换或移除不会删除原文件。'),
                ]),
                Section::make('课时目标与内容')->schema([
                    Textarea::make('intro')->label('课时简介')->default('')->dehydrateStateUsing(fn ($state) => $state ?? ''),
                    Textarea::make('goal')->label('本课要做成什么')->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('objectives')->label('课时目标清单')->default([]),
                    MarkdownEditor::make('content')->label('课时正文')->disableToolbarButtons(['attachFiles']),
                    Repeater::make('steps')->label('课时大纲与操作步骤')->default([])->collapsible()->itemLabel(fn (array $state) => $state['title'] ?? '新步骤')->addActionLabel('增加步骤')->schema([
                        TextInput::make('title')->label('阶段标题')->required(),
                        Textarea::make('body')->label('怎么做、得到什么')->rows(4)->default('')->dehydrateStateUsing(fn ($state) => $state ?? ''),
                    ])->required(fn (Get $get) => $get('status') === 'published'),
                ]),
                Section::make('Agent输入、代码与资料')->schema([
                    Textarea::make('prompt')->label('交给Agent的Prompt')->rows(6)->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')->required(fn (Get $get) => $get('status') === 'published'),
                    TextInput::make('code_filename')->label('示例代码文件名')->maxLength(255),
                    Textarea::make('code')->label('示例代码')->rows(8),
                    Repeater::make('resources')->label('文本资料与附件')->default([])->collapsible()->itemLabel(fn (array $state) => $state['label'] ?? '新资料')->schema([
                        TextInput::make('name')->label('唯一文件名')->required()->regex('/\A[a-z0-9][a-z0-9._-]*\z/')->helperText('小写字母、数字、点、短横线或下划线；不能包含路径。'),
                        TextInput::make('label')->label('显示名称')->required(),
                        Textarea::make('content')->label('文件文本内容')->required()->rows(6),
                    ]),
                ]),
                Section::make('人工验收')->description('已有学习记录的验收项与分值不能直接修改；需要调整时创建新的课时草稿。')->schema([
                    TagsInput::make('checks')->label('可逐项验证的验收标准')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                ]),
            ]);
    }
}
