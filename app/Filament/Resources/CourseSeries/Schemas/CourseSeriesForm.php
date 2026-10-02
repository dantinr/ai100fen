<?php

namespace App\Filament\Resources\CourseSeries\Schemas;

use Filament\Forms\Components\MarkdownEditor;
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
                Section::make('课程基本信息')->columns(2)->schema([
                    TextInput::make('title')->label('课程名称')->required()->maxLength(255)
                        ->helperText('描述要做成的真实结果，工具只是手段。')->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, string $operation, Get $get, Set $set) {
                            if ($operation === 'create' && blank($get('slug'))) {
                                $set('slug', Str::slug($state ?? ''));
                            }
                        }),
                    TextInput::make('slug')->label('课程地址标识')->required()->maxLength(150)->regex('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/')->unique(ignoreRecord: true)->helperText('英文小写、数字和短横线，例如 make-a-personal-site。'),
                    Select::make('category')->label('主要价值类别')->options(['solve' => 'Solve · 解决一个问题', 'create' => 'Create · 创作一个作品', 'explore' => 'Explore · 探索一个可能'])->required()->native(false),
                    Select::make('status')->label('课程状态')->options(['draft' => '草稿', 'published' => '已发布', 'archived' => '已归档'])->required()->default('draft')->live()
                        ->disableOptionWhen(fn (string $value, string $operation) => $operation === 'create' && $value === 'published')
                        ->helperText('先保存草稿，再添加并发布课时，最后发布课程。归档保留内容和学习记录。'),
                    Toggle::make('is_free')->label('整门课程完整免费')->default(false)->live()->afterStateUpdated(fn (bool $state, Set $set) => $set('price', $state ? '0.00' : '100.00'))->helperText('完整免费须全部课时已发布，验收权重合计100。付费课程试看在课时中单独配置。'),
                    TextInput::make('minutes')->label('预计总时长（分钟）')->numeric()->integer()->minValue(1)->maxValue(65535)->required()->default(100),
                    TextInput::make('price')->label('单课程价格（元）')->readOnly()->default('100.00')->dehydrateStateUsing(fn (Get $get) => $get('is_free') ? '0.00' : '100.00')->helperText('当前固定定价：付费100元，完整免费0元。'),
                    TagsInput::make('recommendation_keywords')->label('推荐关键词')->default([])->helperText('填写用户可能提出的任务意图；按回车添加。'),
                    MarkdownEditor::make('description')->label('课程介绍')->disableToolbarButtons(['attachFiles'])->columnSpanFull(),
                    TagsInput::make('objectives')->label('课程目标')->default([])->columnSpanFull()->helperText('逐项描述阶段成果，按回车添加；最终成果与验收请填写下方定义。'),
                ]),
                Section::make('真实成果与课程宪章')->description('发布前六项定义必须齐全；Explore以实验和证据结论验收，失败或证伪也可完成。')->schema([
                    Textarea::make('user_intent')->label('用户意图')->rows(3)->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')->required(fn (Get $get) => $get('status') === 'published'),
                    Textarea::make('final_outcome')->label('最终成果')->rows(3)->default('')->dehydrateStateUsing(fn ($state) => $state ?? '')->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('completion_criteria')->label('整个任务的完成标准')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('agent_role')->label('Agent负责什么')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                    TagsInput::make('human_judgment_required')->label('人必须作出哪些判断')->default([])->required(fn (Get $get) => $get('status') === 'published'),
                ]),
            ]);
    }
}
