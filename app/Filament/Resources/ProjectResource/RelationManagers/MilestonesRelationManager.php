<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use App\Filament\Concerns\ReadOnlyWithoutOwnerUpdate;
use App\Models\Milestone;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MilestonesRelationManager extends RelationManager
{
    use ReadOnlyWithoutOwnerUpdate;

    protected static string $relationship = 'milestones';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('projects.milestones.models');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('projects.milestones.name'))->required()->maxLength(255),
            Select::make('phase_id')->label(__('projects.milestones.phase'))->options(fn (): array => $this->getOwnerRecord()->phases()->pluck('name', 'id')->all())->native(false),
            DatePicker::make('due_date')->label(__('projects.milestones.due_date'))->native(false),
        ])->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('due_date')
            ->columns([
                TextColumn::make('name')->label(__('projects.milestones.name')),
                TextColumn::make('phase.name')->label(__('projects.milestones.phase'))->placeholder('—'),
                TextColumn::make('due_date')
                    ->label(__('projects.milestones.due_date'))
                    ->date('d.m.Y')
                    ->color(fn (Milestone $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->description(fn (Milestone $record): ?string => $record->isOverdue() ? __('projects.milestones.overdue') : null)
                    ->placeholder('—'),
                IconColumn::make('completed_at')->label(__('projects.milestones.completed_at'))->boolean()->getStateUsing(fn (Milestone $record): bool => $record->completed_at !== null),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([
                ActionGroup::make([
                    Action::make('complete')
                        ->authorize('update')
                        ->label(__('projects.milestones.complete'))
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->visible(fn (Milestone $record): bool => $record->completed_at === null)
                        ->action(fn (Milestone $record) => $record->update(['completed_at' => now()])),
                    Action::make('reopen')
                        ->authorize('update')
                        ->label(__('projects.milestones.reopen'))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->visible(fn (Milestone $record): bool => $record->completed_at !== null)
                        ->action(fn (Milestone $record) => $record->update(['completed_at' => null])),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }
}
