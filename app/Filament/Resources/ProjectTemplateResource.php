<?php

namespace App\Filament\Resources;

use App\Enums\ProjectType;
use App\Enums\TaskPriority;
use App\Filament\CostCenterField;
use App\Filament\Resources\ProjectTemplateResource\Pages\CreateProjectTemplate;
use App\Filament\Resources\ProjectTemplateResource\Pages\EditProjectTemplate;
use App\Filament\Resources\ProjectTemplateResource\Pages\ListProjectTemplates;
use App\Models\ProjectTemplate;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProjectTemplateResource extends Resource
{
    protected static ?string $model = ProjectTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?int $navigationSort = 30;

    public static function getModelLabel(): string
    {
        return __('projects.templates.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.templates.models');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('projects.navigation_group');
    }

    public static function form(Schema $schema): Schema
    {
        $phaseOptions = fn (Get $get): array => collect($get('../../phases') ?? [])->pluck('name')->filter()->mapWithKeys(fn (string $name): array => [$name => $name])->all();

        return $schema->components([
            Section::make()
                ->columns(3)
                ->schema([
                    TextInput::make('name')->label(__('projects.templates.name'))->required()->maxLength(255),
                    Select::make('type')->label(__('projects.fields.type'))->options(ProjectType::class)->native(false),
                    TextInput::make('duration_days')->label(__('projects.templates.duration_days'))->numeric()->minValue(0)->suffix(__('projects.templates.days')),
                    Textarea::make('description')->label(__('projects.fields.description'))->rows(2)->columnSpanFull(),
                ]),
            Tabs::make()
                ->columnSpanFull()
                ->tabs([
                    Tab::make(__('projects.phases.models'))
                        ->schema([
                            Repeater::make('phases')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('name')->label(__('projects.phases.name'))->required()->live(onBlur: true),
                                    TextInput::make('start_offset_days')->label(__('projects.templates.start_offset'))->numeric(),
                                    TextInput::make('end_offset_days')->label(__('projects.templates.end_offset'))->numeric(),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->addActionLabel(__('projects.templates.add_phase'))
                                ->reorderableWithButtons()
                                ->defaultItems(0),
                        ]),
                    Tab::make(__('projects.milestones.models'))
                        ->schema([
                            Repeater::make('milestones')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('name')->label(__('projects.milestones.name'))->required(),
                                    Select::make('phase')->label(__('projects.milestones.phase'))->options($phaseOptions)->native(false),
                                    TextInput::make('due_offset_days')->label(__('projects.templates.due_offset'))->numeric(),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->addActionLabel(__('projects.templates.add_milestone'))
                                ->reorderableWithButtons()
                                ->defaultItems(0),
                        ]),
                    Tab::make(__('projects.tasks.models'))
                        ->schema([
                            Repeater::make('tasks')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('title')->label(__('projects.tasks.title'))->required()->columnSpan(2),
                                    Select::make('phase')->label(__('projects.tasks.phase'))->options($phaseOptions)->native(false),
                                    Select::make('priority')->label(__('projects.tasks.priority'))->options(TaskPriority::class)->default(TaskPriority::Normal)->native(false),
                                    TextInput::make('trade')->label(__('projects.tasks.trade')),
                                    TextInput::make('due_offset_days')->label(__('projects.templates.due_offset'))->numeric(),
                                    Textarea::make('description')->label(__('projects.tasks.description'))->rows(2)->columnSpanFull(),
                                    Repeater::make('checklist')
                                        ->label(__('projects.tasks.checklist'))
                                        ->simple(TextInput::make('text')->label(__('projects.tasks.checklist_item'))->required())
                                        ->addActionLabel(__('projects.tasks.add_checklist_item'))
                                        ->defaultItems(0)
                                        ->columnSpanFull(),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                                ->addActionLabel(__('projects.templates.add_task'))
                                ->collapsible()
                                ->reorderableWithButtons()
                                ->defaultItems(0),
                        ]),
                    Tab::make(__('projects.costs.positions'))
                        ->schema([
                            Repeater::make('budget_positions')
                                ->hiddenLabel()
                                ->schema([
                                    TextInput::make('name')->label(__('projects.costs.position_name'))->required(),
                                    CostCenterField::make(),
                                    TextInput::make('budget')->label(__('projects.costs.budget'))->numeric()->suffix('€'),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                ->addActionLabel(__('projects.templates.add_position'))
                                ->reorderableWithButtons()
                                ->defaultItems(0),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('projects.templates.name'))->searchable()->sortable()->weight('medium'),
                TextColumn::make('type')->label(__('projects.fields.type'))->badge()->placeholder('—'),
                TextColumn::make('duration_days')->label(__('projects.templates.duration_days'))->suffix(' '.__('projects.templates.days'))->placeholder('—'),
                TextColumn::make('phases')->label(__('projects.phases.models'))->state(fn (ProjectTemplate $record): int => count($record->phases ?? [])),
                TextColumn::make('tasks')->label(__('projects.tasks.models'))->state(fn (ProjectTemplate $record): int => count($record->tasks ?? [])),
                TextColumn::make('budget_positions')->label(__('projects.costs.positions'))->state(fn (ProjectTemplate $record): int => count($record->budget_positions ?? [])),
                TextColumn::make('updated_at')->label(__('form-builder::form-builder.fields.updated_at'))->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('name')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ReplicateAction::make()->mutateRecordDataUsing(fn (array $data): array => [...collect($data)->except(['id', 'created_at', 'updated_at'])->all(), 'name' => $data['name'].' ('.__('form-builder::form-builder.actions.copy').')']),
                    DeleteAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjectTemplates::route('/'),
            'create' => CreateProjectTemplate::route('/create'),
            'edit' => EditProjectTemplate::route('/{record}/edit'),
        ];
    }
}
