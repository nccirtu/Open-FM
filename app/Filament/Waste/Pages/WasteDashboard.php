<?php

namespace App\Filament\Waste\Pages;

use App\Enums\PropertyUsageType;
use App\Filament\Integrations\MapWidget\WasteContainerMapWidget;
use App\Filament\Waste\Widgets\HazardousWasteWidget;
use App\Filament\Waste\Widgets\PickupExceptionsWidget;
use App\Filament\Waste\Widgets\UpcomingPickupsWidget;
use App\Filament\Waste\Widgets\WasteByFractionChartWidget;
use App\Filament\Waste\Widgets\WasteFlowWidget;
use App\Filament\Waste\Widgets\WasteGoalsWidget;
use App\Filament\Waste\Widgets\WasteStatsWidget;
use App\Models\Property;
use App\Support\EnergyFilters;
use App\Support\Plugins\Plugins;
use App\Support\WasteFilters;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use JohnRivera7\FilamentWidgetGrid\Concerns\HasWidgetGrid;

class WasteDashboard extends BaseDashboard
{
    use HasFiltersForm;
    use HasWidgetGrid;

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.dashboard');
    }

    public function getTitle(): string
    {
        return __('waste.pages.dashboard');
    }

    public function getWidgets(): array
    {
        return [
            WasteStatsWidget::class,
            WasteByFractionChartWidget::class,
            WasteFlowWidget::class,
            PickupExceptionsWidget::class,
            UpcomingPickupsWidget::class,
            HazardousWasteWidget::class,
            WasteGoalsWidget::class,
            ...(Plugins::active('map-widget') ? [WasteContainerMapWidget::class] : []),
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
                                ->whereHas('wasteContainers')
                                ->orderBy('property_number')
                                ->pluck('property_number', 'id')
                                ->all())
                            ->placeholder(__('waste.filters.all_properties'))
                            ->searchable(),
                        Select::make('kind')
                            ->label(__('waste.filters.kind'))
                            ->options(WasteFilters::kindOptions())
                            ->default(WasteFilters::KIND_OPERATIONS)
                            ->selectablePlaceholder(false)
                            ->native(false),
                    ]),
            ]);
    }

    public function getFiltersSessionKey(): string
    {
        return WasteFilters::sessionKey();
    }

    public function updatedFilters(): void
    {
        session()->put($this->getFiltersSessionKey(), $this->filters);

        $this->dispatch(WasteFilters::EVENT, filters: $this->filters);
    }
}
