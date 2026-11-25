<?php

namespace App\Filament\Resources\TaskResource\Pages;

use App\Filament\Resources\TaskResource;
use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    public function getTabs(): array
    {
        $userId = Filament::auth()->id();

        return [
            'mine' => Tab::make(__('projects.tasks.my_tasks'))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->assignedTo($userId)->open())
                ->badge(fn (): int => Task::query()->assignedTo($userId)->open()->count()),
            'all' => Tab::make(__('projects.tasks.all_tasks')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'mine';
    }
}
