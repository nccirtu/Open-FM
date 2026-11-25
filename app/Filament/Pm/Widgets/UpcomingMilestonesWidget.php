<?php

namespace App\Filament\Pm\Widgets;

use App\Models\Milestone;
use App\Models\Project;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingMilestonesWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 11;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Project::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.widgets.upcoming_milestones'))
            ->modelLabel(__('projects.milestones.model'))
            ->pluralModelLabel(__('projects.milestones.models'))
            ->query(
                Milestone::query()
                    ->whereNull('completed_at')
                    ->whereNotNull('due_date')
                    ->whereHas('project', fn ($query) => $query->open())
                    ->with('project')
                    ->orderBy('due_date')
            )
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('name')
                    ->label(__('projects.milestones.name'))
                    ->limit(35),
                TextColumn::make('project.name')
                    ->label(__('projects.tasks.project'))
                    ->limit(25),
                TextColumn::make('due_date')
                    ->label(__('projects.milestones.due_date'))
                    ->date('d.m.Y')
                    ->color(fn (Milestone $record): string => $record->isOverdue() ? 'danger' : 'gray'),
            ]);
    }
}
