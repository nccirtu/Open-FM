<?php

namespace App\Filament\Resources\PropertyResource\RelationManagers;

use App\Enums\ProjectHealth;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProjectsRelationManager extends RelationManager
{
    protected static string $relationship = 'projects';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.models');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('code')->label(__('projects.fields.code'))->placeholder('—'),
                TextColumn::make('name')->label(__('projects.fields.name'))->searchable(),
                TextColumn::make('status')->label(__('projects.fields.status'))->badge(),
                TextColumn::make('effective_health')->label(__('projects.fields.health'))->badge()->state(fn (Project $record): ProjectHealth => $record->effectiveHealth()),
                TextColumn::make('manager.name')->label(__('projects.fields.manager'))->placeholder('—'),
                TextColumn::make('end_date')->label(__('projects.fields.end_date'))->date('d.m.Y')->placeholder('—'),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('filament-actions::create.single.label'))
                    ->icon('heroicon-o-plus')
                    ->url(fn (): string => ProjectResource::getUrl('create', ['property_id' => $this->getOwnerRecord()->getKey()], panel: 'pm')),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Project $record): string => ProjectResource::getUrl('view', ['record' => $record], panel: 'pm')),
            ]);
    }
}
