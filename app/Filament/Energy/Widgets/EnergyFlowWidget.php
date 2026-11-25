<?php

namespace App\Filament\Energy\Widgets;

use App\Enums\EnergyMedium;
use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Energy\Concerns\RendersEnergyFlow;
use App\Models\Meter;
use App\Models\Property;
use App\Services\Energy\EnergyFlow;
use Filament\Widgets\Widget;

class EnergyFlowWidget extends Widget
{
    use InteractsWithEnergyFilters;
    use RendersEnergyFlow;

    public static int $gridW = 12;

    public static int $gridH = 11;

    protected string $view = 'filament.energy.widgets.energy-flow';

    public ?string $propertyId = null;

    public string $medium = 'electricity';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', Meter::class) ?? false;
    }

    public function mount(): void
    {
        $this->syncProperty();
    }

    protected function energyFiltersApplied(): void
    {
        $this->syncProperty();
    }

    public function hasFixedProperty(): bool
    {
        return $this->filters()->propertyId !== null;
    }

    /**
     * @return array<string, string>
     */
    public function getPropertyOptions(): array
    {
        return $this->filters()->properties()->whereHas('meters')->orderBy('property_number')->pluck('property_number', 'id')->all();
    }

    /**
     * @return array<string, string>
     */
    public function getMediumOptions(): array
    {
        return collect(EnergyMedium::cases())->mapWithKeys(fn (EnergyMedium $medium): array => [$medium->value => $medium->getLabel()])->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getLayout(): array
    {
        $property = filled($this->propertyId) ? Property::query()->find($this->propertyId) : null;
        $medium = EnergyMedium::tryFrom($this->medium) ?? EnergyMedium::Electricity;

        if ($property === null) {
            return $this->sankey(['nodes' => [], 'links' => []]);
        }

        $filters = $this->filters();
        $flow = app(EnergyFlow::class)->build($property, $medium, $filters->from, $filters->to);

        return [...$this->sankey($flow), 'unit' => $medium->evaluationUnit(), 'color' => $medium->chartColor()];
    }

    private function syncProperty(): void
    {
        $filters = $this->filters();

        if ($filters->propertyId !== null) {
            $this->propertyId = $filters->propertyId;

            return;
        }

        $options = $this->getPropertyOptions();

        if (! array_key_exists((string) $this->propertyId, $options)) {
            $this->propertyId = $filters->scope(Meter::query()->topLevel())->orderBy('id')->value('property_id') ?? array_key_first($options);
        }
    }
}
