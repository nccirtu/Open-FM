<?php

namespace App\Filament\RelationManagers;

use App\Filament\Fm\Resources\MaintenancePlanResource;
use App\Models\MaintenancePlan;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaintenancePlansRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenancePlans';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('maintenance.plans.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->maintenancePlans()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('next_due_at')
            ->columns([
                TextColumn::make('name')->label(__('maintenance.fields.name'))->searchable()->weight('medium'),
                TextColumn::make('asset.name')->label(__('maintenance.fields.asset'))->placeholder('—'),
                TextColumn::make('type')->label(__('maintenance.fields.type'))->badge(),
                TextColumn::make('interval_value')->label(__('maintenance.fields.interval'))->state(fn (MaintenancePlan $record): string => trim($record->interval_value.' '.($record->interval_unit?->getLabel() ?? $record->interval_unit ?? ''))),
                TextColumn::make('next_due_at')->label(__('maintenance.fields.next_due_at'))->date('d.m.Y')->sortable()->placeholder('—'),
                IconColumn::make('is_active')->label(__('maintenance.fields.is_active'))->boolean(),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MaintenancePlan $record): string => MaintenancePlanResource::getUrl('view', ['record' => $record], panel: 'fm')),
            ]);
    }
}
