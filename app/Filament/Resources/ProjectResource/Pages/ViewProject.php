<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Resources\ProjectResource;
use App\Models\ProjectTemplate;
use App\Services\ProjectTemplateApplier;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Gate;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                Action::make('board')
                    ->label(__('projects.tasks.board'))
                    ->icon('heroicon-o-view-columns')
                    ->url(fn (): string => ProjectBoard::getUrl(['record' => $this->getRecord()])),
                Action::make('gantt')
                    ->label(__('projects.gantt.title'))
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn (): string => ProjectGantt::getUrl(['record' => $this->getRecord()])),
                Action::make('applyTemplate')
                    ->label(__('projects.templates.apply'))
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->authorize(fn (): bool => Gate::allows('update', $this->getRecord()) && Gate::allows('viewAny', ProjectTemplate::class))
                    ->schema([
                        Select::make('template_id')
                            ->label(__('projects.templates.model'))
                            ->options(fn (): array => ProjectTemplate::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data): void {
                        abort_unless(Gate::allows('update', $this->getRecord()), 403);

                        $template = ProjectTemplate::query()->findOrFail($data['template_id']);
                        app(ProjectTemplateApplier::class)->apply($template, $this->getRecord());

                        Notification::make()->title(__('projects.templates.applied', ['name' => $template->name]))->success()->send();
                    }),
                Action::make('saveAsTemplate')
                    ->label(__('projects.templates.save_as'))
                    ->icon('heroicon-o-arrow-down-on-square')
                    ->color('gray')
                    ->authorize(fn (): bool => Gate::allows('create', ProjectTemplate::class))
                    ->schema([
                        TextInput::make('name')->label(__('projects.templates.name'))->required()->default(fn (): string => $this->getRecord()->name),
                    ])
                    ->action(function (array $data): void {
                        $template = app(ProjectTemplateApplier::class)->captureFrom($this->getRecord()->load(['phases', 'milestones', 'tasks', 'budgetPositions']), $data['name']);

                        Notification::make()->title(__('projects.templates.saved', ['name' => $template->name]))->success()->send();
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ];
    }
}
