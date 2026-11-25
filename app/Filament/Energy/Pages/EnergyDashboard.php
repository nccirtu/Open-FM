<?php

namespace App\Filament\Energy\Pages;

use App\Enums\PropertyUsageType;
use App\Filament\Energy\Widgets\CalibrationDueWidget;
use App\Filament\Energy\Widgets\ConsumptionByAssetChartWidget;
use App\Filament\Energy\Widgets\ConsumptionTrendChartWidget;
use App\Filament\Energy\Widgets\EnergyFlowWidget;
use App\Filament\Energy\Widgets\EnergyGoalsWidget;
use App\Filament\Energy\Widgets\EnergyStatsWidget;
use App\Filament\Energy\Widgets\PropertyEnergyComparisonWidget;
use App\Filament\Energy\Widgets\SuspiciousReadingsWidget;
use App\Models\Property;
use App\Support\EnergyFilters;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class EnergyDashboard extends BaseDashboard
{
    use HasFiltersForm;
    use HasWidgetGrid;

    public function getWidgets(): array
    {
        return [
            EnergyStatsWidget::class,
            ConsumptionTrendChartWidget::class,
            EnergyGoalsWidget::class,
            EnergyFlowWidget::class,
            ConsumptionByAssetChartWidget::class,
            PropertyEnergyComparisonWidget::class,
            SuspiciousReadingsWidget::class,
            CalibrationDueWidget::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->extraAttributes([])
            ->partiallyRender()
            ->components([
                Section::make()
                    ->columnSpanFull()
                    ->columns(['md' => 2, 'xl' => 4])
                    ->schema([
                        Select::make('period')
                            ->label(__('energy.filters.period'))
                            ->options(EnergyFilters::periodOptions())
                            ->default(EnergyFilters::DEFAULT_PERIOD)
                            ->selectablePlaceholder(false)
                            ->native(false),
                        DatePicker::make('from')
                            ->label(__('energy.filters.from'))
                            ->native(false)
                            ->maxDate(today())
                            ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                        DatePicker::make('until')
                            ->label(__('energy.filters.until'))
                            ->native(false)
                            ->maxDate(today())
                            ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                        Select::make('usage_type')
                            ->label(__('property.fields.usage_type'))
                            ->options(PropertyUsageType::class)
                            ->placeholder(__('energy.filters.all_usage_types'))
                            ->native(false)
                            ->afterStateUpdated(fn (Set $set) => $set('property_id', null)),
                        Select::make('property_id')
                            ->label(__('property.model'))
                            ->options(fn (Get $get): array => Property::query()
                                ->when(filled($get('usage_type')), fn ($query) => $query->where('usage_type', $get('usage_type') instanceof PropertyUsageType ? $get('usage_type')->value : $get('usage_type')))
                                ->whereHas('meters')
                                ->orderBy('property_number')
                                ->pluck('property_number', 'id')
                                ->all())
                            ->placeholder(__('energy.filters.all_properties'))
                            ->searchable(),
                    ]),
            ]);
    }

    public function getFiltersSessionKey(): string
    {
        return EnergyFilters::sessionKey();
    }

    public function updatedFilters(): void
    {
        session()->put($this->getFiltersSessionKey(), $this->filters);

        $this->dispatch(EnergyFilters::EVENT, filters: $this->filters);
    }
}
