<?php

namespace App\Filament\Waste\Widgets;

use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Filament\Waste\Pages\HazardousRegisterPage;
use App\Models\Property;
use App\Models\WasteCollection;
use App\Services\Waste\HazardousWasteRegister;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class HazardousWasteWidget extends Widget
{
    use InteractsWithWasteFilters;

    public static int $gridW = 7;

    public static int $gridH = 8;

    protected string $view = 'filament.waste.widgets.hazardous-waste';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    /**
     * @return Collection<int, array{property: Property, mass_t: float, threshold_t: float, share: float}>
     */
    public function getRows(): Collection
    {
        $filters = $this->filters();

        return app(HazardousWasteRegister::class)
            ->thresholds($filters->year(), $filters->properties()->pluck('id')->all())
            ->take(6);
    }

    /**
     * @return array{open: int, overdue: int}
     */
    public function getRegisterCounts(): array
    {
        $register = app(HazardousWasteRegister::class);
        $missing = $this->filters()->scope($register->query())->whereNull('document_number')->get(['id', 'organization_id', 'collected_at', 'document_number']);
        $overdue = $missing->filter(fn (WasteCollection $collection): bool => $register->status($collection) === 'overdue')->count();

        return ['open' => $missing->count() - $overdue, 'overdue' => $overdue];
    }

    public function getRegisterUrl(): string
    {
        return HazardousRegisterPage::getUrl();
    }
}
