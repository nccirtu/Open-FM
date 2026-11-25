<?php

namespace App\Filament\Fm\Widgets;

use App\Models\MaintenanceOrder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class UpcomingMaintenanceWidget extends TableWidget
{
    public static int $gridW = 6;

    public static int $gridH = 12;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', MaintenanceOrder::class) ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('app.widgets.upcoming_maintenance'))
            ->modelLabel(__('maintenance.orders.model'))
            ->pluralModelLabel(__('maintenance.orders.models'))
            ->query(
                MaintenanceOrder::query()
                    ->open()
                    ->whereNotNull('due_at')
                    ->where('due_at', '<=', now()->addDays(30))
                    ->with(['asset.property', 'plan'])
                    ->orderBy('due_at')
            )
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('due_at')
                    ->label(__('maintenance.fields.due_at'))
                    ->date('d.m.Y')
                    ->color(fn (MaintenanceOrder $record): string => $record->dueColor()),
                TextColumn::make('plan.name')
                    ->label(__('maintenance.plans.model'))
                    ->limit(35),
                TextColumn::make('asset.name')
                    ->label(__('maintenance.fields.asset')),
                TextColumn::make('asset.property.property_number')
                    ->label(__('tickets.fields.property')),
            ]);
    }
}
