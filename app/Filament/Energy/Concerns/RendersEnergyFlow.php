<?php

namespace App\Filament\Energy\Concerns;

use App\Filament\Energy\Resources\MeterResource;
use App\Filament\Resources\AssetResource;
use App\Services\Energy\EnergyFlow;
use App\Support\SankeyLayout;
use Illuminate\Support\Str;

trait RendersEnergyFlow
{
    /**
     * @param  array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}  $flow
     * @return array<string, mixed>
     */
    protected function sankey(array $flow): array
    {
        return SankeyLayout::compute($flow, 900, max(220, min(620, 60 + count($flow['nodes']) * 34)), labelSpace: 270);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    public function nodeUrl(array $node): ?string
    {
        return match ($node['kind'] ?? null) {
            'meter' => MeterResource::getUrl('view', ['record' => $node['id']]),
            'asset' => AssetResource::getUrl('view', ['record' => Str::after($node['id'], 'asset:')], panel: 'fm'),
            default => null,
        };
    }

    public function isUnassigned(string $id): bool
    {
        return $id === EnergyFlow::UNASSIGNED;
    }
}
