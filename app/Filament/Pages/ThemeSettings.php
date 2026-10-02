<?php

namespace App\Filament\Pages;

use App\Services\ThemeConfiguration;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ThemeSettings extends Page
{
    protected static ?string $title = 'Theme 配置';

    protected static ?string $navigationLabel = 'Theme 配置';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.theme-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->is_admin === true;
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->form->fill(['theme' => app(ThemeConfiguration::class)->selected() ?? 'environment']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('全站主题')->description('保存后前台新请求立即生效，页面结构、课程权限、价格与学习进度保持一致。')->schema([
                Select::make('theme')->label('当前主题')->required()->native(false)
                    ->options(['environment' => '跟随 APP_THEME', ...collect(config('themes.themes'))->map(fn (array $theme) => $theme['name'])->all()])
                    ->helperText('Pop 为默认主题；Future 为机制验证骨架。新增主题先登记代码注册表与构建入口。'),
            ]),
        ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);
        app(ThemeConfiguration::class)->save(Filament::auth()->user(), $this->form->getState()['theme']);
        Notification::make()->title('主题配置已保存')->body('刷新前台页面即可查看效果。')->success()->send();
    }
}
