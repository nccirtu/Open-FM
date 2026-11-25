<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\EnergyMedium;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Models\Meter;
use App\Models\Property;
use App\Services\Energy\EnergyReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class PropertyEnergyComparisonWidget extends TableWidget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 6;

    public static int $gridH = 8;

    /**
     * @var array<string, array{current: float, previous: float}>
     */
    private array $cache = [];

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
            ->heading(fn (): string => __('energy.comparison.title'))
            ->description(fn (): string => $this->filters()->label())
            ->query(fn (): Builder => $this->filters()->properties()->whereHas('meters'))
            ->defaultSort('property_number')
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5)
            ->columns([
                TextColumn::make('property_number')
                    ->label(__('property.model'))
                    ->description(fn (Property $record): ?string => $record->city)
                    ->sortable(),
                TextColumn::make('usage_type')
                    ->label(__('property.fields.usage_type'))
                    ->badge(),
                TextColumn::make('energy')
                    ->label(__('energy.comparison.energy'))
                    ->state(fn (Property $record): string => number_format($this->values($record)['current'], 0, ',', '.').' kWh')
                    ->alignEnd(),
                TextColumn::make('intensity')
                    ->label(__('energy.comparison.intensity'))
                    ->state(function (Property $record): ?string {
                        $area = $record->referenceArea();

                        return $area ? number_format($this->values($record)['current'] / $area, 1, ',', '.').' kWh/m²' : null;
                    })
                    ->placeholder('—')
                    ->alignEnd(),
                TextColumn::make('change')
                    ->label(__('energy.comparison.change'))
                    ->state(function (Property $record): ?string {
                        $values = $this->values($record);

                        if ($values['previous'] <= 0) {
                            return null;
                        }

                        $change = ($values['current'] - $values['previous']) / $values['previous'] * 100;

                        return ($change >= 0 ? '+' : '').number_format($change, 1, ',', '.').' %';
                    })
                    ->color(fn (?string $state): string => $state === null ? 'gray' : (str_starts_with($state, '+') ? 'danger' : 'success'))
                    ->placeholder('—')
                    ->alignEnd(),
            ]);
    }

    /**
     * @return array{current: float, previous: float}
     */
    private function values(Property $property): array
    {
        return $this->cache[$property->getKey()] ??= (function () use ($property): array {
            $report = app(EnergyReport::class);
            $filters = $this->filters();
            [$previousFrom, $previousTo] = $filters->previousYear();
            $meters = Meter::query()
                ->topLevel()
                ->where('property_id', $property->getKey())
                ->whereIn('medium', EnergyMedium::energyValues());

            return [
                'current' => $report->consumption(clone $meters, $filters->from, $filters->to),
                'previous' => $report->consumption(clone $meters, $previousFrom, $previousTo),
            ];
        })();
    }
}
