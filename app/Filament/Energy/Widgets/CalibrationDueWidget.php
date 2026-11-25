<?php

namespace App\Filament\Energy\Widgets;

use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Energy\Resources\MeterResource;
use App\Models\Meter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class CalibrationDueWidget extends TableWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 6;

    public static int $gridH = 8;

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    protected function energyFiltersApplied(): void
    {
        $this->resetPage();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('energy.calibration.title'))
            ->modelLabel(__('energy.meter.model'))
            ->pluralModelLabel(__('energy.meter.models'))
            ->query(fn (): Builder => $this->filters()->scope(Meter::query())->with('property')->whereNotNull('calibration_valid_until')->whereDate('calibration_valid_until', '<=', now()->addDays(180)))
            ->defaultSort('calibration_valid_until')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->recordUrl(fn (Meter $record): string => MeterResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading(__('energy.calibration.empty'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('energy.fields.name'))
                    ->description(fn (Meter $record): ?string => $record->number),
                TextColumn::make('property.property_number')
                    ->label(__('property.model')),
                TextColumn::make('medium')
                    ->label(__('energy.fields.medium'))
                    ->badge(),
                TextColumn::make('calibration_valid_until')
                    ->label(__('energy.fields.calibration_valid_until'))
                    ->date('d.m.Y')
                    ->badge()
                    ->color(fn (Meter $record): string => $record->calibrationColor()),
            ]);
    }
}
