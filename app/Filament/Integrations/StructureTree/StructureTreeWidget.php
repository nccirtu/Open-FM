<?php

namespace App\Filament\Integrations\StructureTree;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Filament\Resources\AssetResource;
use App\Models\Asset;
use App\Models\Property;
use App\Models\StructureNode;
use Cafm\StructureTree\StructureTreeWidget as BaseStructureTreeWidget;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class StructureTreeWidget extends BaseStructureTreeWidget
{
    public Property $property;

    /** @var array<int, float>|null */
    protected ?array $areaSums = null;

    protected ?Collection $allAssets = null;

    public function getHeading(): ?string
    {
        return __('property.structure.models');
    }

    protected function canEditStructure(): bool
    {
        return Gate::allows('update', $this->property);
    }

    public function moveNode(int|string $nodeId, int|string|null $parentId, array $orderedIds): void
    {
        abort_unless($this->canEditStructure(), 403);

        parent::moveNode($nodeId, $parentId, $orderedIds);
    }

    public function moveAttachment(string $key, int|string|null $parentId, ?string $parentKey = null): void
    {
        abort_unless($this->canEditStructure(), 403);

        parent::moveAttachment($key, $parentId, $parentKey);
    }

    public function createNodeAction(): Action
    {
        return parent::createNodeAction()->authorize(fn (): bool => $this->canEditStructure());
    }

    public function addChildAction(): Action
    {
        return parent::addChildAction()->authorize(fn (): bool => $this->canEditStructure());
    }

    public function editNodeAction(): Action
    {
        return parent::editNodeAction()->authorize(fn (): bool => $this->canEditStructure());
    }

    public function deleteNodeAction(): Action
    {
        return parent::deleteNodeAction()->authorize(fn (): bool => $this->canEditStructure());
    }

    protected function getTreeQuery(): Builder
    {
        return StructureNode::query()
            ->where('property_id', $this->property->getKey());
    }

    protected function getNodeFormSchema(): array
    {
        return [
            Select::make('parent_id')
                ->label(__('property.structure.parent'))
                ->options(fn (): array => StructureNode::locationOptions($this->property->getKey()))
                ->searchable()
                ->nullable(),
            TextInput::make('name')
                ->label(__('property.structure.name'))
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label(__('property.structure.type'))
                ->options(StructureNodeType::class)
                ->default(StructureNodeType::Room->value)
                ->required()
                ->native(false)
                ->live(),
            Select::make('room_use')
                ->label(__('property.structure.room_use'))
                ->options(RoomUse::class)
                ->nullable()
                ->native(false)
                ->visible(fn (Get $get): bool => in_array($get('type'), [StructureNodeType::Room->value, StructureNodeType::Zone->value], true)),
            TextInput::make('area')
                ->label(__('property.structure.area'))
                ->numeric()
                ->minValue(0)
                ->nullable(),
            TextInput::make('height')
                ->label(__('property.structure.height'))
                ->numeric()
                ->minValue(0)
                ->nullable()
                ->visible(fn (Get $get): bool => $get('type') === StructureNodeType::Room->value),
            Toggle::make('heated')
                ->label(__('property.structure.heated'))
                ->visible(fn (Get $get): bool => $get('type') === StructureNodeType::Room->value),
        ];
    }

    protected function mutateNodeDataBeforeCreate(array $data): array
    {
        $data['property_id'] = $this->property->getKey();

        return $data;
    }

    protected function getNodeIcon(Model $node): ?string
    {
        if ($node->room_use === RoomUse::Corridor) {
            return 'heroicon-o-arrows-right-left';
        }

        return $node->type?->getIcon();
    }

    protected function getNodeBadge(Model $node): ?array
    {
        if ($node->type === null) {
            return null;
        }

        return [
            'label' => $node->type->getLabel(),
            'color' => $node->type->getColor(),
        ];
    }

    protected function getNodeMeta(Model $node): ?string
    {
        return $node->area !== null ? self::formatArea((float) $node->area) : null;
    }

    protected function getNodeAttachments(Model $node): array
    {
        return $this->assetsByNode()
            ->get($node->getKey(), collect())
            ->map(fn (Asset $asset): array => $this->presentAsset($asset))
            ->all();
    }

    protected function getUnattachedItems(): array
    {
        return $this->assetsByNode()
            ->get('', collect())
            ->map(fn (Asset $asset): array => $this->presentAsset($asset))
            ->all();
    }

    protected function handleAttachmentMove(string $key, int|string|null $parentId, ?string $parentKey = null): void
    {
        $asset = $this->resolveAssetKey($key);

        if ($asset === null) {
            return;
        }

        if ($parentKey !== null) {
            $target = $this->resolveAssetKey($parentKey);

            if ($target === null || ! $this->isValidParentAsset($asset, $target)) {
                return;
            }

            $asset->parent_asset_id = $target->getKey();
            $asset->structure_node_id = $target->structure_node_id;
            $asset->save();

            $this->allAssets = null;

            return;
        }

        if ($parentId !== null && ! $this->getTreeQuery()->whereKey($parentId)->exists()) {
            return;
        }

        $asset->parent_asset_id = null;
        $asset->structure_node_id = $parentId;
        $asset->save();

        $this->allAssets = null;
    }

    protected function resolveAssetKey(string $key): ?Asset
    {
        if (! str_starts_with($key, 'asset-')) {
            return null;
        }

        return Asset::query()
            ->where('property_id', $this->property->getKey())
            ->whereKey(substr($key, strlen('asset-')))
            ->first();
    }

    protected function isValidParentAsset(Asset $asset, Asset $target): bool
    {
        for ($cursor = $target; $cursor !== null; $cursor = $cursor->parentAsset) {
            if ($cursor->getKey() === $asset->getKey()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{key: string, label: string, icon: string, badge: ?array{label: string, color: string}, meta: ?string, url: ?string, children: array<mixed>}
     */
    protected function presentAsset(Asset $asset): array
    {
        return [
            'key' => 'asset-'.$asset->getKey(),
            'label' => $asset->name,
            'icon' => 'heroicon-o-rectangle-stack',
            'badge' => null,
            'meta' => $asset->assetType?->name,
            'url' => AssetResource::getUrl('edit', ['record' => $asset]),
            'children' => $this->assetsByParent()
                ->get($asset->getKey(), collect())
                ->map(fn (Asset $child): array => $this->presentAsset($child))
                ->all(),
        ];
    }

    /**
     * @return Collection<int, Asset>
     */
    protected function allAssets(): Collection
    {
        return $this->allAssets ??= Asset::query()
            ->where('property_id', $this->property->getKey())
            ->with('assetType')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, Asset>>
     */
    protected function assetsByNode(): Collection
    {
        return $this->allAssets()
            ->whereNull('parent_asset_id')
            ->groupBy(fn (Asset $asset): string => $asset->structure_node_id ?? '');
    }

    /**
     * @return Collection<int, Collection<int, Asset>>
     */
    protected function assetsByParent(): Collection
    {
        return $this->allAssets()
            ->whereNotNull('parent_asset_id')
            ->groupBy('parent_asset_id');
    }

    protected function refreshTree(): void
    {
        $this->areaSums = null;
        $this->allAssets = null;

        parent::refreshTree();
    }

    protected function getNodeSum(Model $node): ?string
    {
        $sum = $this->areaSums()[$node->getKey()] ?? 0.0;

        $hasChildren = $this->getNodes()->contains(fn (Model $candidate): bool => $candidate->parent_id === $node->getKey());

        if (! $hasChildren || $sum <= 0) {
            return null;
        }

        return 'Σ '.self::formatArea($sum);
    }

    public function getHeaderSummary(): array
    {
        $nodes = $this->getNodes();

        $totalArea = (float) $nodes->sum(fn (Model $node): float => (float) ($node->area ?? 0));

        return array_filter([
            __('property.structure.total_area') => $totalArea > 0 ? self::formatArea($totalArea) : null,
            __('property.structure.rooms_count') => (string) $nodes->where('type', StructureNodeType::Room)->count(),
            __('property.structure.nodes_count') => (string) $nodes->count(),
            __('property.structure.assets_count') => (string) $this->allAssets()->count(),
        ]);
    }

    /**
     * @return array<int, float>
     */
    protected function areaSums(): array
    {
        if ($this->areaSums !== null) {
            return $this->areaSums;
        }

        $byParent = $this->getNodes()->groupBy(fn (Model $node): string => $node->parent_id ?? '');

        $sums = [];

        $walk = function (Model $node) use (&$walk, $byParent, &$sums): float {
            $sum = (float) ($node->area ?? 0);

            foreach ($byParent->get($node->getKey(), collect()) as $child) {
                $sum += $walk($child);
            }

            return $sums[$node->getKey()] = $sum;
        };

        foreach ($byParent->get('', collect()) as $root) {
            $walk($root);
        }

        return $this->areaSums = $sums;
    }

    protected static function formatArea(float $area): string
    {
        return number_format($area, 2, ',', '.').' m²';
    }
}
