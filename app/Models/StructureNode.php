<?php

namespace App\Models;

use App\Enums\RoomUse;
use App\Enums\StructureNodeType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\StructureNodeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class StructureNode extends Model
{
    /** @use HasFactory<StructureNodeFactory> */
    use BelongsToOrganization, HasFactory;

    use HasRecursiveRelationships;
    use HasUuids;
    use InteractsWithMediaFiles;

    protected $fillable = [
        'property_id',
        'parent_id',
        'type',
        'name',
        'sort',
        'level',
        'room_use',
        'area',
        'height',
        'heated',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StructureNodeType::class,
            'room_use' => RoomUse::class,
            'sort' => 'integer',
            'level' => 'integer',
            'area' => 'decimal:2',
            'height' => 'decimal:2',
            'heated' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StructureNode $node): void {
            if ($node->property_id === null && $node->parent_id !== null) {
                $parent = static::query()->withoutGlobalScopes()->find($node->parent_id);

                $node->property_id = $parent?->property_id;
                $node->organization_id ??= $parent?->organization_id;
            }
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id')
            ->with('children')
            ->orderBy('sort')
            ->orderBy('name');
    }

    public function wasteContainers(): HasMany
    {
        return $this->hasMany(WasteContainer::class);
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function supplyingMeters(): BelongsToMany
    {
        return $this->belongsToMany(Meter::class)->withPivot('share');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function fullPath(): string
    {
        $segments = [$this->name];
        $node = $this;

        while (($node = $node->parent) !== null) {
            array_unshift($segments, $node->name);
        }

        return implode(' › ', $segments);
    }

    /**
     * @return array<string, string>
     */
    public static function locationOptions(int|string|null $propertyId): array
    {
        if (blank($propertyId)) {
            return [];
        }

        $byParent = static::query()
            ->where('property_id', $propertyId)
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (self $node): string => $node->parent_id ?? '');

        $options = [];

        $walk = function (string $parentId, string $prefix) use (&$walk, $byParent, &$options): void {
            foreach ($byParent->get($parentId, collect()) as $node) {
                $label = $prefix === '' ? $node->name : "{$prefix} › {$node->name}";
                $options[$node->id] = $label;
                $walk($node->id, $label);
            }
        };

        $walk('', '');

        return $options;
    }
}
