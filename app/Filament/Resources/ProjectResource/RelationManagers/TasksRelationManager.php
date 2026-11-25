<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Filament\Resources\TaskResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TasksRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'tasks';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.tasks.models');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(TaskResource::formComponents(projectId: $this->getOwnerRecord()->getKey(), withProject: false))->columns(2);
    }

    public function table(Table $table): Table
    {
        return TaskResource::buildTable($table, withProject: false)
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])]);
    }
}
