<?php

namespace App\Filament\RelationManagers;

use App\Filament\Fm\Resources\MaintenanceOrderResource;
use App\Models\MaintenanceOrder;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MaintenanceOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceOrders';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('maintenance.orders.models');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->{static::$relationship}()->count();

        return $count > 0 ? (string) $count : null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_at', 'desc')
            ->columns([
                TextColumn::make('plan.name')->label(__('maintenance.plans.model'))->weight('medium'),
                TextColumn::make('asset.name')->label(__('maintenance.fields.asset'))->placeholder('—'),
                TextColumn::make('due_at')->label(__('maintenance.fields.due_at'))->date('d.m.Y')->sortable()->color(fn (MaintenanceOrder $record): string => $record->dueColor()),
                TextColumn::make('status')->label(__('maintenance.fields.status'))->badge(),
                TextColumn::make('completed_at')->label(__('maintenance.fields.completed_at'))->dateTime('d.m.Y H:i')->placeholder('—'),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('filament-actions::view.single.label'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MaintenanceOrder $record): string => MaintenanceOrderResource::getUrl('view', ['record' => $record], panel: 'fm')),
            ]);
    }
}
