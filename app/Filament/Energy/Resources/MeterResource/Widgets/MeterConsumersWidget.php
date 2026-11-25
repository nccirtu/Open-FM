<?php

namespace App\Filament\Energy\Resources\MeterResource\Widgets;

use App\Filament\Energy\Concerns\RendersEnergyFlow;
use App\Models\Meter;
use App\Services\Energy\EnergyFlow;
use App\Services\Energy\EnergyReport;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class MeterConsumersWidget extends Widget
{
    use RendersEnergyFlow;

    protected string $view = 'filament.energy.widgets.meter-consumers';

    protected int|string|array $columnSpan = 'full';

    public ?Model $record = null;

    public int $year;

    public function mount(): void
    {
        $this->year = now()->year;
    }

    /**
     * @return array<int, int>
     */
    public function getYearOptions(): array
    {
        return range(now()->year, now()->year - 4);
    }

    public function hasConsumers(): bool
    {
        return $this->record instanceof Meter
            && ($this->record->measuredAssets()->exists() || $this->record->children()->exists());
    }

    /**
     * @return array{layout: array<string, mixed>, consumers: array<int, array{id: string, label: string, kind: string, value: float, percent: float}>, total: float, unit: string, color: string}
     */
    public function getData(): array
    {
        /** @var Meter $meter */
        $meter = $this->record;
        $from = CarbonImmutable::create($this->year)->startOfYear();
        $to = min($from->endOfYear(), CarbonImmutable::today());
        $flow = app(EnergyFlow::class)->buildFor($meter, $from, $to);
        $total = app(EnergyReport::class)->consumption([$meter], $from, $to);

        return [
            'layout' => [...$this->sankey($flow), 'unit' => $meter->evaluationUnit(), 'color' => $meter->medium->chartColor()],
            'consumers' => $flow['links'] === [] ? [] : self::consumers($flow, $meter->getKey(), $total),
            'total' => $total,
            'unit' => $meter->evaluationUnit(),
            'color' => $meter->medium->chartColor(),
        ];
    }

    /**
     * @param  array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}  $flow
     * @return array<int, array{id: string, label: string, kind: string, value: float, percent: float}>
     */
    public static function consumers(array $flow, string $rootId, float $total): array
    {
        $sources = array_flip(array_column($flow['links'], 'source'));
        $incoming = [];

        foreach ($flow['links'] as $link) {
            $incoming[$link['target']] = ($incoming[$link['target']] ?? 0.0) + $link['value'];
        }

        return collect($flow['nodes'])
            ->reject(fn (array $node): bool => $node['id'] === $rootId || isset($sources[$node['id']]))
            ->map(fn (array $node): array => [
                'id' => $node['id'],
                'label' => $node['label'],
                'kind' => $node['kind'],
                'value' => $incoming[$node['id']] ?? 0.0,
                'percent' => $total > 0 ? ($incoming[$node['id']] ?? 0.0) / $total * 100 : 0.0,
            ])
            ->sortBy([fn (array $a, array $b): int => ($a['kind'] === 'rest') <=> ($b['kind'] === 'rest'), fn (array $a, array $b): int => $b['value'] <=> $a['value']])
            ->values()
            ->all();
    }
}
