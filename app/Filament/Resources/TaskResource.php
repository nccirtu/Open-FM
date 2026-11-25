<?php

namespace App\Filament\Resources;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Filament\Resources\TaskResource\Pages\CreateTask;
use App\Filament\Resources\TaskResource\Pages\EditTask;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-check-circle';

    protected static ?int $navigationSort = 20;

    public static function getModelLabel(): string
    {
        return __('projects.tasks.model');
    }

    public static function getPluralModelLabel(): string
    {
        return __('projects.tasks.models');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('projects.navigation_group');
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->with('project');

        if ($user === null || $user->can('view_all_project')) {
            return $query;
        }

        return $query->where(fn (Builder $builder): Builder => $builder
            ->assignedTo($user->getKey())
            ->orWhereHas('project', fn (Builder $projects): Builder => $projects->managedBy($user)));
    }

    /**
     * @return Builder<Project>
     */
    public static function updatableProjects(): Builder
    {
        $user = auth()->user();

        return Project::query()
            ->open()
            ->when($user === null || ! $user->can('update_project'), fn (Builder $query): Builder => $query->whereRaw('1 = 0'))
            ->when($user !== null && ! $user->can('update_all_project'), fn (Builder $query): Builder => $query->managedBy($user));
    }

    public static function authorizeProjectUpdate(mixed $projectId): void
    {
        abort_if(filled($projectId) && ! self::updatableProjects()->whereKey($projectId)->exists(), 403);
    }

    public static function form(Schema $schema): Schema
    {
        $components = collect(self::formComponents())->keyBy(fn (Component $component): string => $component->getName());
        $pick = fn (string ...$names): array => $components->only($names)->values()->all();

        return $schema->components([
            Section::make(__('projects.tasks.sections.task'))
                ->columns(2)
                ->schema($pick('project_id', 'title', 'description')),
            Section::make(__('projects.tasks.sections.planning'))
                ->columns(2)
                ->schema($pick('status', 'priority', 'assignee_id', 'due_date', 'trade')),
            Section::make(__('projects.tasks.sections.assignment'))
                ->columns(2)
                ->collapsible()
                ->schema($pick('phase_id', 'milestone_id', 'blocked_by_task_id')),
            Section::make(__('projects.tasks.sections.checklist'))
                ->collapsible()
                ->schema($pick('checklist')),
        ]);
    }

    /**
     * @return array<int, Component>
     */
    public static function formComponents(?string $projectId = null, bool $withProject = true): array
    {
        $projectOf = fn (Get $get): ?string => $projectId ?? ($get('project_id') ?: null);

        return array_values(array_filter([
            $withProject ? Select::make('project_id')
                ->label(__('projects.tasks.project'))
                ->options(fn (): array => self::updatableProjects()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required()
                ->live()
                ->native(false)
                ->columnSpanFull() : null,
            TextInput::make('title')->label(__('projects.tasks.title'))->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->label(__('projects.tasks.description'))->rows(3)->columnSpanFull(),
            Select::make('status')->label(__('projects.tasks.status'))->options(TaskStatus::class)->default(TaskStatus::Todo)->required()->native(false),
            Select::make('priority')->label(__('projects.tasks.priority'))->options(TaskPriority::class)->default(TaskPriority::Normal)->required()->native(false),
            Select::make('assignee_id')->label(__('projects.tasks.assignee'))->options(fn (): array => ProjectResource::userOptions())->searchable()->native(false),
            DatePicker::make('start_date')->label(__('projects.tasks.start_date'))->native(false),
            DatePicker::make('due_date')->label(__('projects.tasks.due_date'))->native(false),
            TextInput::make('trade')->label(__('projects.tasks.trade'))->maxLength(100),
            Select::make('phase_id')
                ->label(__('projects.tasks.phase'))
                ->options(fn (Get $get): array => ($id = $projectOf($get)) ? ProjectPhase::query()->where('project_id', $id)->orderBy('sort')->pluck('name', 'id')->all() : [])
                ->native(false),
            Select::make('milestone_id')
                ->label(__('projects.tasks.milestone'))
                ->options(fn (Get $get): array => ($id = $projectOf($get)) ? Milestone::query()->where('project_id', $id)->orderBy('due_date')->pluck('name', 'id')->all() : [])
                ->native(false),
            Select::make('blocked_by_task_id')
                ->label(__('projects.tasks.blocked_by'))
                ->options(fn (Get $get, ?Model $record): array => ($id = $projectOf($get)) ? Task::query()->where('project_id', $id)->when($record instanceof Task, fn (Builder $query) => $query->whereKeyNot($record->getKey()))->orderBy('title')->pluck('title', 'id')->all()
                    : [])
                ->searchable()
                ->native(false),
            Repeater::make('checklist')
                ->label(__('projects.tasks.checklist'))
                ->schema([
                    TextInput::make('text')->label(__('projects.tasks.checklist_item'))->required()->columnSpan(3),
                    Checkbox::make('done')->label(__('projects.tasks.checklist_done'))->inline(false),
                ])
                ->columns(4)
                ->addActionLabel(__('projects.tasks.add_checklist_item'))
                ->defaultItems(0)
                ->columnSpanFull(),
        ]));
    }

    public static function table(Table $table): Table
    {
        return self::buildTable($table, withProject: true)
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()->authorizeIndividualRecords('delete')])]);
    }

    public static function buildTable(Table $table, bool $withProject): Table
    {
        return $table
            ->columns(array_values(array_filter([
                TextColumn::make('title')->label(__('projects.tasks.title'))->searchable()->sortable()->weight('medium')
                    ->description(fn (Task $record): ?string => $record->isBlocked() ? __('projects.tasks.blocked').': '.$record->blockedBy?->title : null),
                $withProject ? TextColumn::make('project.name')->label(__('projects.tasks.project'))->sortable()->searchable() : null,
                TextColumn::make('status')->label(__('projects.tasks.status'))->badge()->sortable(),
                TextColumn::make('priority')->label(__('projects.tasks.priority'))->badge()->sortable(),
                TextColumn::make('assignee.name')->label(__('projects.tasks.assignee'))->placeholder('—'),
                TextColumn::make('due_date')
                    ->label(__('projects.tasks.due_date'))
                    ->date('d.m.Y')
                    ->sortable()
                    ->color(fn (Task $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->placeholder('—'),
                TextColumn::make('trade')->label(__('projects.tasks.trade'))->placeholder('—')->toggleable(),
                TextColumn::make('checklist')->label(__('projects.tasks.checklist'))->state(fn (Task $record): ?string => $record->checklistProgress())->placeholder('—')->toggleable(),
            ])))
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('status')->label(__('projects.tasks.status'))->options(TaskStatus::class)->multiple(),
                SelectFilter::make('priority')->label(__('projects.tasks.priority'))->options(TaskPriority::class),
                SelectFilter::make('assignee_id')->label(__('projects.tasks.assignee'))->options(fn (): array => ProjectResource::userOptions()),
                Filter::make('overdue')
                    ->label(__('projects.filters.overdue'))
                    ->query(fn (Builder $query): Builder => $query->where('status', '!=', TaskStatus::Done->value)->whereDate('due_date', '<', now()->startOfDay()))
                    ->toggle(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Task::query()->open()->assignedTo((string) Filament::auth()->id())->count();

        return $count > 0 ? (string) $count : null;
    }
}
