<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Enums\TaskStatus;
use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Relaticle\Flowforge\Board;
use Relaticle\Flowforge\BoardResourcePage;
use Relaticle\Flowforge\Column;

class ProjectBoard extends BoardResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.project-board';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(ProjectResource::canView($this->record), 403);
    }

    public function moveCard(string $cardId, string $targetColumnId, ?string $afterCardId = null, ?string $beforeCardId = null): void
    {
        abort_unless(Gate::allows('update', $this->record), 403);

        parent::moveCard($cardId, $targetColumnId, $afterCardId, $beforeCardId);
    }

    public function getTitle(): string
    {
        return __('projects.tasks.board').': '.$this->record->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            ProjectResource::getUrl() => __('projects.models'),
            ProjectResource::getUrl('view', ['record' => $this->record]) => $this->record->name,
            __('projects.tasks.board'),
        ];
    }

    public function board(Board $board): Board
    {
        return $board
            ->query(fn (): Builder => Task::query()->where('project_id', $this->record->getKey())->with(['assignee', 'blockedBy']))
            ->recordTitleAttribute('title')
            ->columnIdentifier('status')
            ->positionIdentifier('position')
            ->searchable(['title', 'trade'])
            ->columns(collect(TaskStatus::cases())->map(fn (TaskStatus $status): Column => Column::make($status->value)->label($status->getLabel())->color($status->getColor()))->all())
            ->cardSchema(fn (Schema $schema): Schema => $schema->components([
                TextEntry::make('assignee.name')->hiddenLabel()->icon('heroicon-o-user')->placeholder(__('projects.tasks.unassigned')),
                TextEntry::make('due_date')->hiddenLabel()->icon('heroicon-o-calendar')->date('d.m.Y')->color(fn (Task $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextEntry::make('priority')->hiddenLabel()->badge(),
                TextEntry::make('trade')->hiddenLabel()->icon('heroicon-o-wrench')->placeholder(''),
            ]))
            ->columnActions([
                Action::make('create')
                    ->label(__('projects.tasks.new_in_column'))
                    ->icon('heroicon-o-plus')
                    ->authorize(fn (): bool => Gate::allows('update', $this->record))
                    ->schema(TaskResource::formComponents(projectId: $this->record->getKey(), withProject: false))
                    ->action(function (array $data, array $arguments): void {
                        Task::query()->create([
                            ...$data,
                            'project_id' => $this->record->getKey(),
                            'status' => $arguments['column'] ?? TaskStatus::Todo->value,
                        ]);
                    }),
            ])
            ->cardActions([
                Action::make('edit')
                    ->label(__('projects.tasks.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->authorize('update')
                    ->fillForm(fn (Task $record): array => $record->attributesToArray())
                    ->schema(TaskResource::formComponents(projectId: $this->record->getKey(), withProject: false))
                    ->action(fn (Task $record, array $data) => $record->update($data)),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('list')
                ->label(__('projects.tasks.models'))
                ->icon('heroicon-o-list-bullet')
                ->color('gray')
                ->url(fn (): string => ProjectResource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('projects.tasks.board');
    }

    public function getRecord(): Project
    {
        return $this->record;
    }
}
