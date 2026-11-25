<?php

namespace App\Filament\Waste\Widgets;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Models\WastePickup;
use Carbon\CarbonImmutable;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class UpcomingPickupsWidget extends TableWidget
{
    use InteractsWithWasteFilters;

    protected static ?int $sort = 5;

    public static int $gridW = 5;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WastePickup::class) ?? false;
    }

    protected function wasteFiltersApplied(): void
    {
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        $today = CarbonImmutable::today();

        return $table
            ->heading(__('waste.widgets.upcoming'))
            ->modelLabel(__('waste.models.pickup.singular'))
            ->pluralModelLabel(__('waste.models.pickup.plural'))
            ->query(fn (): Builder => $this->filters()->scope(WastePickup::query())
                ->with(['container.fraction', 'property'])
                ->where(fn (Builder $query) => $query
                    ->where(fn (Builder $query) => $query
                        ->where('status', WastePickupStatus::Planned->value)
                        ->whereBetween('due_on', [$today->addDay()->toDateString(), $today->addDays(7)->toDateString()]))
                    ->orWhere('status', WastePickupStatus::Requested->value)))
            ->defaultSort('due_on')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('waste.widgets.upcoming_empty'))
            ->columns([
                TextColumn::make('due_on')
                    ->label(__('waste.fields.due_on'))
                    ->date('D d.m.')
                    ->placeholder(__('waste.pickup_status.requested')),
                TextColumn::make('container.fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->badge()
                    ->color(fn (WastePickup $record): array => Color::hex($record->container?->fraction?->displayColor() ?? '#94a3b8'))
                    ->description(fn (WastePickup $record): ?string => $record->container?->number),
                TextColumn::make('property.property_number')
                    ->label(__('property.model')),
            ]);
    }
}
