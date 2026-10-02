<?php

namespace App\Filament\Resources\LiveSessions;

use App\Filament\Resources\LiveSessions\Pages\CreateLiveSession;
use App\Filament\Resources\LiveSessions\Pages\EditLiveSession;
use App\Filament\Resources\LiveSessions\Pages\ListLiveSessions;
use App\Models\LiveSession;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LiveSessionResource extends Resource
{
    protected static ?string $model = LiveSession::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;
    protected static ?string $recordTitleAttribute = 'title';
    protected static ?string $modelLabel = '直播场次';
    protected static ?string $pluralModelLabel = '直播管理';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('场次安排')->columns(2)->schema([
                TextInput::make('title')->label('主题')->required()->maxLength(160)->columnSpanFull(),
                DateTimePicker::make('starts_at')->label('开始时间')->timezone('Asia/Shanghai')->seconds(false)->required(),
                DateTimePicker::make('ends_at')->label('结束时间')->timezone('Asia/Shanghai')->seconds(false)->required()->after('starts_at'),
                Select::make('status')->label('状态')->options([
                    'draft' => '草稿', 'scheduled' => '已排期', 'live' => '直播中',
                    'processing' => '回放整理中', 'replay' => '可看回放', 'cancelled' => '已取消',
                ])->required()->default('draft')->native(false)->live()
                    ->helperText('草稿不在前台展示；其他状态保存后对所有访客可见。'),
                Textarea::make('description')->label('简介')->rows(3)->columnSpanFull(),
            ]),
            Section::make('入口与回放')->description('当前只支持公开场次。会员或课程专属直播需要先完成服务端访问权限。')->columns(2)->schema([
                TextInput::make('meeting_provider')->label('直播平台')->maxLength(80)->placeholder('例如：腾讯会议'),
                TextInput::make('meeting_url')->label('课堂入口')->url()->startsWith('https://')->maxLength(2048)
                    ->required(fn (Get $get) => $get('status') === 'live')
                    ->helperText('仅在状态为“直播中”时向访客开放。'),
                TextInput::make('replay_url')->label('回放链接')->url()->startsWith('https://')->maxLength(2048)
                    ->required(fn (Get $get) => $get('status') === 'replay')
                    ->helperText('仅在状态为“可看回放”时向访客开放。')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->label('ID')->sortable(),
            TextColumn::make('title')->label('主题')->searchable(),
            TextColumn::make('starts_at')->label('开始时间')->dateTime('Y-m-d H:i', timezone: 'Asia/Shanghai')->sortable(),
            TextColumn::make('status')->label('状态')->badge()->formatStateUsing(fn (string $state) => [
                'draft' => '草稿', 'scheduled' => '已排期', 'live' => '直播中',
                'processing' => '回放整理中', 'replay' => '可看回放', 'cancelled' => '已取消',
            ][$state] ?? $state),
        ])->recordActions([EditAction::make()->label('编辑场次')])->defaultSort('starts_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLiveSessions::route('/'),
            'create' => CreateLiveSession::route('/create'),
            'edit' => EditLiveSession::route('/{record}/edit'),
        ];
    }
}
