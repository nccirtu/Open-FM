<?php

namespace App\Filament\Waste\Concerns;

use App\Support\WasteFilters;
use Livewire\Attributes\On;

trait InteractsWithWasteFilters
{
    /**
     * @var array<string, mixed>|null
     */
    public ?array $wasteFilters = null;

    public function bootInteractsWithWasteFilters(): void
    {
        $this->wasteFilters ??= session()->get(WasteFilters::sessionKey());
    }

    /**
     * @param  array<string, mixed>|null  $filters
     */
    #[On(WasteFilters::EVENT)]
    public function applyWasteFilters(?array $filters): void
    {
        $this->wasteFilters = $filters;
        $this->wasteFiltersApplied();
    }

    protected function wasteFiltersApplied(): void {}

    public function filters(): WasteFilters
    {
        return WasteFilters::fromArray($this->wasteFilters);
    }
}
