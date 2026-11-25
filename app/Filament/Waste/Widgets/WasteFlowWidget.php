<?php

namespace App\Filament\Waste\Widgets;

use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Models\WasteCollection;
use App\Services\Waste\WasteReport;
use App\Support\SankeyLayout;
use Filament\Widgets\Widget;

class WasteFlowWidget extends Widget
{
    use InteractsWithWasteFilters;

    protected static ?int $sort = 3;

    public static int $gridW = 5;

    public static int $gridH = 9;

    protected string $view = 'filament.waste.widgets.waste-flow';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteCollection::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLayout(): array
    {
        $flow = app(WasteReport::class)->flow($this->filters());

        return SankeyLayout::compute($flow, 640, max(220, min(520, 40 + count($flow['nodes']) * 30)), labelSpace: 220);
    }
}
