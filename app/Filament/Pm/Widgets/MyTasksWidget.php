<?php

namespace App\Filament\Pm\Widgets;

use App\Models\Task;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class MyTasksWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 11;

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.widgets.my_tasks'))
            ->modelLabel(__('projects.tasks.model'))
            ->pluralModelLabel(__('projects.tasks.models'))
            ->query(
                Task::query()
                    ->open()
                    ->assignedTo(auth()->id())
                    ->with('project')
                    ->orderByRaw('due_date is null, due_date')
            )
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('title')
                    ->label(__('projects.tasks.title'))
                    ->limit(40),
                TextColumn::make('project.name')
                    ->label(__('projects.tasks.project'))
                    ->limit(25),
                TextColumn::make('due_date')
                    ->label(__('projects.tasks.due_date'))
                    ->date('d.m.Y')
                    ->color(fn (Task $record): string => $record->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('status')
                    ->label(__('projects.tasks.status'))
                    ->badge(),
            ]);
    }
}
