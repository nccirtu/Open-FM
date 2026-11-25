<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\ListsManagedRecords;
use App\Filament\Resources\ProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProjects extends ListRecords
{
    use ListsManagedRecords;

    protected static string $resource = ProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }

    protected function mineTabLabel(): string
    {
        return __('projects.tabs.mine');
    }

    protected function allTabLabel(): string
    {
        return __('projects.tabs.all');
    }
}
