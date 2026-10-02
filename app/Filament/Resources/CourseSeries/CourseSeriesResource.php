<?php

namespace App\Filament\Resources\CourseSeries;

use App\Filament\Resources\CourseSeries\Pages\CreateCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\EditCourseSeries;
use App\Filament\Resources\CourseSeries\Pages\ListCourseSeries;
use App\Filament\Resources\CourseSeries\RelationManagers\LessonsRelationManager;
use App\Filament\Resources\CourseSeries\Schemas\CourseSeriesForm;
use App\Filament\Resources\CourseSeries\Tables\CourseSeriesTable;
use App\Models\CourseSeries;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CourseSeriesResource extends Resource
{
    protected static ?string $model = CourseSeries::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $modelLabel = '课程';

    protected static ?string $pluralModelLabel = '课程管理';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return CourseSeriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CourseSeriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            LessonsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCourseSeries::route('/'),
            'create' => CreateCourseSeries::route('/create'),
            'edit' => EditCourseSeries::route('/{record}/edit'),
        ];
    }
}
