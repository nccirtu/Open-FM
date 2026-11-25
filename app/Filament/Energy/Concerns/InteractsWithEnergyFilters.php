<?php

namespace App\Filament\Energy\Concerns;

use App\Support\EnergyFilters;
use Livewire\Attributes\On;

trait InteractsWithEnergyFilters
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $energyFilters = null;

    public function bootInteractsWithEnergyFilters(): void
    {
        $this->energyFilters ??= session()->get(EnergyFilters::sessionKey());
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    #[On(EnergyFilters::EVENT)]
    public function applyEnergyFilters(?array $filters): void
    {
        $this->energyFilters = $filters;
        $this->energyFiltersApplied();
    }

    protected function energyFiltersApplied(): void {}

    public function filters(): EnergyFilters
    {
        return EnergyFilters::fromArray($this->energyFilters);
    }
}
