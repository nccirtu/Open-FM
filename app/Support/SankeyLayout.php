<?php

namespace App\Support;

final class SankeyLayout
{
    /**
     * @param  array{nodes: array<int, array{id: string, label: string, column: int, kind: string}>, links: array<int, array{source: string, target: string, value: float}>}  $flow
     * @return array{width: int, height: int, nodeWidth?: int, nodes: array<int, array<string, mixed>>, links: array<int, array<string, mixed>>}
     */
    public static function compute(array $flow, int $width = 900, int $height = 420, int $nodeWidth = 14, int $gap = 12, int $labelSpace = 190): array
    {
        $nodes = [];

        foreach ($flow['nodes'] as $node) {
            $nodes[$node['id']] = [...$node, 'in' => 0.0, 'out' => 0.0];
        }

        foreach ($flow['links'] as $link) {
            if (! isset($nodes[$link['source']], $nodes[$link['target']])) {
                continue;
            }

            $nodes[$link['source']]['out'] += $link['value'];
            $nodes[$link['target']]['in'] += $link['value'];
        }

        if ($nodes === []) {
            return ['width' => $width, 'height' => $height, 'nodes' => [], 'links' => []];
        }

        $columns = [];

        foreach ($nodes as $id => $node) {
            $nodes[$id]['value'] = max($node['in'], $node['out']);
            $columns[$node['column']][] = $id;
        }

        ksort($columns);
        $columnCount = max(array_keys($columns)) + 1;
        $usable = $height - 20;
        $scale = INF;

        foreach ($columns as $ids) {
            $sum = array_sum(array_map(fn (string $id): float => $nodes[$id]['value'], $ids));

            if ($sum > 0) {
                $scale = min($scale, ($usable - (count($ids) - 1) * $gap) / $sum);
            }
        }

        $scale = is_finite($scale) ? max($scale, 0.0) : 0.0;
        $step = $columnCount > 1 ? ($width - $labelSpace - $nodeWidth - 10) / ($columnCount - 1) : 0;

        foreach ($columns as $column => $ids) {
            $y = 10.0;

            foreach ($ids as $id) {
                $nodeHeight = max($nodes[$id]['value'] * $scale, 2.0);
                $nodes[$id]['x'] = round(10 + $column * $step, 2);
                $nodes[$id]['y'] = round($y, 2);
                $nodes[$id]['h'] = round($nodeHeight, 2);
                $nodes[$id]['inY'] = $y;
                $nodes[$id]['outY'] = $y;
                $y += $nodeHeight + $gap;
            }
        }

        $links = [];

        foreach ($flow['links'] as $link) {
            if (! isset($nodes[$link['source']], $nodes[$link['target']])) {
                continue;
            }

            $source = &$nodes[$link['source']];
            $target = &$nodes[$link['target']];
            $bandHeight = $link['value'] * $scale;
            $sx = $source['x'] + $nodeWidth;
            $tx = $target['x'];
            $sy = $source['outY'];
            $ty = $target['inY'];
            $mx = ($sx + $tx) / 2;
            $source['outY'] += $bandHeight;
            $target['inY'] += $bandHeight;

            $links[] = [
                'source' => $link['source'],
                'target' => $link['target'],
                'value' => $link['value'],
                'kind' => $source['kind'],
                'path' => sprintf(
                    'M%.1f,%.1f C%.1f,%.1f %.1f,%.1f %.1f,%.1f L%.1f,%.1f C%.1f,%.1f %.1f,%.1f %.1f,%.1f Z',
                    $sx, $sy, $mx, $sy, $mx, $ty, $tx, $ty,
                    $tx, $ty + $bandHeight, $mx, $ty + $bandHeight, $mx, $sy + $bandHeight, $sx, $sy + $bandHeight,
                ),
                'label' => $source['label'].' → '.$target['label'],
            ];

            unset($source, $target);
        }

        return [
            'width' => $width,
            'height' => $height,
            'nodeWidth' => $nodeWidth,
            'nodes' => array_values(array_map(fn (array $node): array => [
                'id' => $node['id'],
                'label' => $node['label'],
                'kind' => $node['kind'],
                'value' => $node['value'],
                'x' => $node['x'],
                'y' => $node['y'],
                'h' => $node['h'],
            ], $nodes)),
            'links' => $links,
        ];
    }
}
