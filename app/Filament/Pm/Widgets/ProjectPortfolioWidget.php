<?php

namespace App\Filament\Pm\Widgets;

use App\Enums\ProjectHealth;
use App\Enums\ProjectPhaseStatus;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class ProjectPortfolioWidget extends TableWidget
{
    public static int $gridW = 12;

    public static int $gridH = 15;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('projects.portfolio.title'))
            ->modelLabel(__('projects.model'))
            ->pluralModelLabel(__('projects.models'))
            ->query(fn (): Builder => Project::query()->open()->with(['property', 'manager']))
            ->defaultSort('end_date')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('effective_health')->label(__('projects.fields.health'))->badge()->state(fn (Project $record): ProjectHealth => $record->effectiveHealth()),
                TextColumn::make('name')->label(__('projects.fields.name'))->weight('medium')->searchable()->description(fn (Project $record): ?string => $record->property?->property_number),
                TextColumn::make('status')->label(__('projects.fields.status'))->badge(),
                TextColumn::make('current_phase')
                    ->label(__('projects.portfolio.phase'))
                    ->state(fn (Project $record): ?string => $record->phases()->where('status', ProjectPhaseStatus::Active->value)->value('name'))
                    ->placeholder('—'),
                TextColumn::make('manager.name')->label(__('projects.fields.manager'))->placeholder('—'),
                TextColumn::make('next_milestone')
                    ->label(__('projects.fields.next_milestone'))
                    ->state(fn (Project $record): ?string => ($milestone = $record->nextMilestone()) ? $milestone->due_date?->format('d.m.Y').' · '.$milestone->name : null)
                    ->color(fn (Project $record): ?string => $record->nextMilestone()?->isOverdue() ? 'danger' : null)
                    ->placeholder('—'),
                TextColumn::make('progress')->label(__('projects.fields.progress'))->state(fn (Project $record): string => $record->progress().' %'),
                TextColumn::make('budget')
                    ->label(__('projects.portfolio.budget'))
                    ->state(fn (Project $record): string => number_format($record->plannedBudget(), 0, ',', '.').' € / '.number_format($record->actualCosts(), 0, ',', '.').' €')
                    ->color(fn (Project $record): ?string => $record->budgetVariance() < 0 ? 'danger' : null),
                TextColumn::make('end_date')->label(__('projects.fields.end_date'))->date('d.m.Y')->sortable()->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
