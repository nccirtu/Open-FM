<?php

namespace App\Filament\Resources;

use App\Enums\ProjectHealth;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Filament\Concerns\HasManagedRecords;
use App\Filament\RelationManagers\FolderFilesRelationManager;
use App\Filament\Resources\ProjectResource\Pages\CreateProject;
use App\Filament\Resources\ProjectResource\Pages\EditProject;
use App\Filament\Resources\ProjectResource\Pages\ListProjects;
use App\Filament\Resources\ProjectResource\Pages\ProjectBoard;
use App\Filament\Resources\ProjectResource\Pages\ProjectGantt;
use App\Filament\Resources\ProjectResource\Pages\ViewProject;
use App\Filament\Resources\ProjectResource\RelationManagers\BudgetPositionsRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\ChangeOrdersRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\CostEntriesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\PhasesRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use App\Filament\Resources\ProjectResource\RelationManagers\WasteCollectionsRelationManager;
use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\Property;
use App\Services\EntityFolders;
use App\Support\Plugins\CustomFieldsBridge;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Slimani\MediaManager\Form\MediaPicker;

class ProjectResource extends Resource
{
    use HasManagedRecords;

    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('projects.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.models');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('projects.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('projects.sections.master'))
                ->columns(2)
                ->schema([
                    TextInput::make('code')->label(__('projects.fields.code'))->maxLength(50),
                    TextInput::make('name')->label(__('projects.fields.name'))->required()->maxLength(255),
                    Select::make('type')->label(__('projects.fields.type'))->options(ProjectType::class)->native(false),
                    Select::make('property_id')
                        ->label(__('projects.fields.property'))
                        ->options(fn (): array => Property::query()->orderBy('property_number')->get()->mapWithKeys(fn (Property $property): array => [$property->getKey() => trim($property->property_number.' '.$property->city)])->all())
                        ->searchable()
                        ->native(false),
                    static::managerSelect(__('projects.fields.manager')),
                    Select::make('status')->label(__('projects.fields.status'))->options(ProjectStatus::class)->default(ProjectStatus::Open)->required()->native(false),
                    Select::make('health')->label(__('projects.fields.health'))->options(ProjectHealth::class)->default(ProjectHealth::Green)->required()->native(false),
                    Textarea::make('description')->label(__('projects.fields.description'))->rows(3)->columnSpanFull(),
                    Select::make('template_id')
                        ->label(__('projects.templates.apply_on_create'))
                        ->helperText(__('projects.templates.apply_on_create_help'))
                        ->options(fn (): array => ProjectTemplate::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->native(false)
                        ->dehydrated(false)
                        ->visibleOn('create')
                        ->columnSpanFull(),
                ]),
            Section::make(__('projects.sections.schedule'))
                ->columns(3)
                ->schema([
                    DatePicker::make('start_date')->label(__('projects.fields.start_date'))->native(false),
                    DatePicker::make('end_date')->label(__('projects.fields.end_date'))->native(false)->afterOrEqual('start_date'),
                    TextInput::make('budget')->label(__('projects.fields.budget'))->numeric()->minValue(0)->suffix('€'),
                ]),
            Section::make(__('projects.sections.files'))
                ->collapsible()
                ->collapsed()
                ->schema([
                    MediaPicker::make('files')->label(__('projects.fields.files'))->multiple()->directory(fn (?Model $record): ?string => EntityFolders::pathFor($record)),
                ]),
            ...CustomFieldsBridge::formComponents('project'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('projects.sections.master'))
                ->columns(3)
                ->schema([
                    TextEntry::make('code')->label(__('projects.fields.code'))->placeholder('—'),
                    TextEntry::make('type')->label(__('projects.fields.type'))->badge()->placeholder('—'),
                    TextEntry::make('status')->label(__('projects.fields.status'))->badge(),
                    TextEntry::make('property.property_number')->label(__('projects.fields.property'))->placeholder('—'),
                    TextEntry::make('manager.name')->label(__('projects.fields.manager'))->placeholder('—'),
                    TextEntry::make('effective_health')
                        ->label(__('projects.fields.effective_health'))
                        ->badge()
                        ->state(fn (Project $record): ProjectHealth => $record->effectiveHealth()),
                    TextEntry::make('start_date')->label(__('projects.fields.start_date'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('end_date')->label(__('projects.fields.end_date'))->date('d.m.Y')->placeholder('—'),
                    TextEntry::make('budget')->label(__('projects.fields.budget'))->money('EUR', locale: 'de')->placeholder('—'),
                    TextEntry::make('progress')->label(__('projects.fields.progress'))->state(fn (Project $record): string => $record->progress().' %'),
                    TextEntry::make('next_milestone')
                        ->label(__('projects.fields.next_milestone'))
                        ->state(fn (Project $record): ?string => ($milestone = $record->nextMilestone()) ? $milestone->name.' · '.$milestone->due_date?->format('d.m.Y') : null)
                        ->placeholder('—'),
                    TextEntry::make('indicators')
                        ->label(__('projects.fields.indicators'))
                        ->badge()
                        ->color('warning')
                        ->state(fn (Project $record): array => array_map(fn (string $key): string => __('projects.indicators.'.$key), $record->indicators()) ?: [__('projects.indicators.none')]),
                    TextEntry::make('description')->label(__('projects.fields.description'))->columnSpanFull()->placeholder('—'),
                ]),
            Section::make(__('projects.costs.section'))
                ->columns(4)
                ->schema([
                    TextEntry::make('planned_budget')->label(__('projects.costs.planned'))->state(fn (Project $record): float => $record->plannedBudget())->money('EUR', locale: 'de'),
                    TextEntry::make('actual_costs')->label(__('projects.costs.actual'))->state(fn (Project $record): float => $record->actualCosts())->money('EUR', locale: 'de'),
                    TextEntry::make('estimated_costs')->label(__('projects.costs.estimated'))->state(fn (Project $record): float => $record->estimatedCosts())->money('EUR', locale: 'de'),
                    TextEntry::make('budget_variance')
                        ->label(__('projects.costs.variance'))
                        ->state(fn (Project $record): float => $record->budgetVariance())
                        ->money('EUR', locale: 'de')
                        ->color(fn (Project $record): string => $record->budgetVariance() < 0 ? 'danger' : 'success')
                        ->weight('bold'),
                ]),
            ...CustomFieldsBridge::infolistComponents('project'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label(__('projects.fields.code'))->searchable()->sortable()->toggleable(),
                TextColumn::make('name')->label(__('projects.fields.name'))->searchable()->sortable()->weight('medium'),
                TextColumn::make('property.property_number')->label(__('projects.fields.property'))->placeholder('—')->sortable(),
                TextColumn::make('manager.name')->label(__('projects.fields.manager'))->placeholder('—'),
                TextColumn::make('status')->label(__('projects.fields.status'))->badge()->sortable(),
                TextColumn::make('effective_health')
                    ->label(__('projects.fields.health'))
                    ->badge()
                    ->state(fn (Project $record): ProjectHealth => $record->effectiveHealth()),
                TextColumn::make('progress')->label(__('projects.fields.progress'))->state(fn (Project $record): string => $record->progress().' %'),
                TextColumn::make('budget_usage')
                    ->label(__('projects.costs.usage'))
                    ->state(fn (Project $record): ?string => ($usage = $record->budgetUsage()) === null ? null : $usage.' %')
                    ->color(fn (Project $record): ?string => match (true) {
                        ($record->budgetUsage() ?? 0) > 100 => 'danger', ($record->budgetUsage() ?? 0) >= 90 => 'warning', default => null
                    })
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('end_date')->label(__('projects.fields.end_date'))->date('d.m.Y')->sortable()->placeholder('—'),
                ...CustomFieldsBridge::tableColumns('project'),
            ])
            ->defaultSort('end_date')
            ->filters([
                SelectFilter::make('status')->label(__('projects.fields.status'))->options(ProjectStatus::class)->multiple(),
                SelectFilter::make('manager_id')->label(__('projects.fields.manager'))->options(fn (): array => self::userOptions()),
                SelectFilter::make('property_id')->label(__('projects.fields.property'))->relationship('property', 'property_number'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('board')
                    ->label(__('projects.tasks.board'))
                    ->icon('heroicon-o-view-columns')
                    ->url(fn (Project $record): string => ProjectBoard::getUrl(['record' => $record])),
                Action::make('gantt')
                    ->label(__('projects.gantt.title'))
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (Project $record): string => ProjectGantt::getUrl(['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PhasesRelationManager::class,
            MilestonesRelationManager::class,
            TasksRelationManager::class,
            BudgetPositionsRelationManager::class,
            CostEntriesRelationManager::class,
            ChangeOrdersRelationManager::class,
            WasteCollectionsRelationManager::class,
            FolderFilesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
            'board' => ProjectBoard::route('/{record}/board'),
            'gantt' => ProjectGantt::route('/{record}/gantt'),
        ];
    }
}
