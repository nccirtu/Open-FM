<?php

namespace App\Filament\Energy\Widgets;

use App\Filament\Energy\Concerns\InteractsWithEnergyFilters;
use App\Filament\Energy\Resources\EnergyGoalResource;
use App\Models\EnergyGoal;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class EnergyGoalsWidget extends Widget
{
    use InteractsWithEnergyFilters;

    public static int $gridW = 4;

    public static int $gridH = 8;

    protected string $view = 'filament.energy.widgets.energy-goals';

    public static function canView(): bool
    {
        return auth()->user()?->can('viewAny', EnergyGoal::class) ?? false;
    }

    /**
     * @return Collection<int, array{goal: EnergyGoal, result: array<string, mixed>}>
     */
    public function getGoals(): Collection
    {
        $order = ['crit' => 0, 'warn' => 1, 'unknown' => 2, 'good' => 3];
        $filters = $this->filters();

        return EnergyGoal::query()
            ->where('year', $filters->year())
            ->with('goalable')
            ->get()
            ->filter(fn (EnergyGoal $goal): bool => $filters->includesGoal($goal))
            ->map(fn (EnergyGoal $goal): array => ['goal' => $goal, 'result' => EnergyGoalResource::evaluation($goal)])
            ->sortBy(fn (array $row): int => $order[$row['result']['status']] ?? 9)
            ->take(6)
            ->values();
    }
}
