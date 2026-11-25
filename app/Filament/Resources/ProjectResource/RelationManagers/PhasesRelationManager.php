<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Enums\ProjectPhaseStatus;
use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PhasesRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'phases';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.phases.models');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('projects.phases.name'))->required()->maxLength(255),
            TextInput::make('sort')->label(__('projects.phases.sort'))->numeric()->default(fn (): int => ($this->getOwnerRecord()->phases()->max('sort') ?? 0) + 1),
            Select::make('status')->label(__('projects.phases.status'))->options(ProjectPhaseStatus::class)->default(ProjectPhaseStatus::Planned)->native(false),
            DatePicker::make('start_date')->label(__('projects.phases.start_date'))->native(false),
            DatePicker::make('end_date')->label(__('projects.phases.end_date'))->native(false),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('sort')->label('#')->sortable(),
                TextColumn::make('name')->label(__('projects.phases.name')),
                TextColumn::make('status')->label(__('projects.phases.status'))->badge(),
                TextColumn::make('start_date')->label(__('projects.phases.start_date'))->date('d.m.Y')->placeholder('—'),
                TextColumn::make('end_date')->label(__('projects.phases.end_date'))->date('d.m.Y')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([ActionGroup::make([EditAction::make(), DeleteAction::make()])]);
    }
}
