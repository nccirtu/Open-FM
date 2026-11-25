<?php

namespace App\Filament\Waste\Widgets;

use App\Enums\WastePickupStatus;
use App\Filament\Waste\Actions\PickupActions;
use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Models\WastePickup;
use Filament\Actions\ActionGroup;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PickupExceptionsWidget extends TableWidget
{
    use InteractsWithWasteFilters;

    protected static ?int $sort = 4;

    public static int $gridW = 7;

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
        return $table
            ->heading(__('waste.widgets.exceptions'))
            ->modelLabel(__('waste.models.pickup.singular'))
            ->pluralModelLabel(__('waste.models.pickup.plural'))
            ->query(fn (): Builder => $this->filters()->scope(WastePickup::query())
                ->with(['container.fraction', 'property'])
                ->where(fn (Builder $query) => $query
                    ->where('status', WastePickupStatus::Requested->value)
                    ->orWhere(fn (Builder $query) => $query
                        ->where('status', WastePickupStatus::Missed->value)
                        ->whereBetween('due_on', [$this->filters()->from()->toDateString(), $this->filters()->to()->toDateString()]))))
            ->defaultSort('due_on', 'desc')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading(__('waste.widgets.exceptions_empty'))
            ->columns([
                TextColumn::make('container.number')
                    ->label(__('waste.models.container.singular'))
                    ->description(fn (WastePickup $record): ?string => $record->property?->property_number),
                TextColumn::make('container.fraction.name')
                    ->label(__('waste.models.fraction.singular'))
                    ->badge()
                    ->color(fn (WastePickup $record): array => Color::hex($record->container?->fraction?->displayColor() ?? '#94a3b8')),
                TextColumn::make('due_on')
                    ->label(__('waste.fields.due_on'))
                    ->date('D d.m.')
                    ->placeholder(fn (WastePickup $record): ?string => $record->requested_at?->format('d.m.')),
                TextColumn::make('status')
                    ->label(__('waste.fields.status'))
                    ->badge()
                    ->description(fn (WastePickup $record): ?string => $record->missed_reason?->getLabel()),
            ])
            ->recordActions([
                ActionGroup::make([
                    PickupActions::plan(),
                    PickupActions::cancel(),
                ]),
            ]);
    }
}
