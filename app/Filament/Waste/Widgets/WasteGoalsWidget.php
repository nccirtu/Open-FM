<?php

namespace App\Filament\Waste\Widgets;

use App\Filament\Waste\Concerns\InteractsWithWasteFilters;
use App\Filament\Waste\Resources\WasteGoals\WasteGoalResource;
use App\Models\WasteGoal;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class WasteGoalsWidget extends Widget
{
    use InteractsWithWasteFilters;

    public static int $gridW = 5;

    public static int $gridH = 8;

    protected string $view = 'filament.waste.widgets.waste-goals';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', WasteGoal::class) ?? false;
    }

    /**
     * @return Collection<int, array{goal: WasteGoal, result: array<string, mixed>}>
     */
    public function getGoals(): Collection
    {
        $order = ['crit' => 0, 'warn' => 1, 'unknown' => 2, 'good' => 3];
        $filters = $this->filters();

        return WasteGoal::query()
            ->where('year', $filters->year())
            ->with('property')
            ->get()
            ->filter(fn (WasteGoal $goal): bool => $filters->includesGoal($goal))
            ->map(fn (WasteGoal $goal): array => ['goal' => $goal, 'result' => WasteGoalResource::evaluation($goal)])
            ->sortBy(fn (array $row): int => $order[$row['result']['status']] ?? 9)
            ->take(6)
            ->values();
    }
}
