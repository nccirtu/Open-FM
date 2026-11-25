<?php

namespace App\Models;

use App\Enums\CostEntryKind;
use App\Enums\EnergyRating;
use App\Enums\PropertyUsageType;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class Property extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasManager;
    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;

    /**
     * @var string
     */
    protected $table = 'properties';

    /**
     * @var array
     */
    protected $guarded = ['organization_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_area' => 'decimal:2',
            'net_area' => 'decimal:2',
            'cleanable_area' => 'decimal:2',
            'heated_volume' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'energy_rating' => EnergyRating::class,
            'usage_type' => PropertyUsageType::class,
            'energy_cert_valid_until' => 'date',
        ];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function structureNodes(): HasMany
    {
        return $this->hasMany(StructureNode::class);
    }

    public function rootStructureNodes(): HasMany
    {
        return $this->hasMany(StructureNode::class)->whereNull('parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function maintenanceOrders(): HasManyThrough
    {
        return $this->hasManyThrough(MaintenanceOrder::class, Asset::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function files(): MorphToMany
    {
        return $this->mediaFiles('files');
    }

    public function meters(): HasMany
    {
        return $this->hasMany(Meter::class);
    }

    public function wasteContainers(): HasMany
    {
        return $this->hasMany(WasteContainer::class);
    }

    public function referenceArea(): ?float
    {
        $area = (float) ($this->net_area ?: $this->gross_area);

        return $area > 0 ? $area : null;
    }

    public function maintenancePlans(): HasManyThrough
    {
        return $this->hasManyThrough(MaintenancePlan::class, Asset::class);
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }

    /**
     * @return Builder<CostEntry>
     */
    public function allCostEntries(): Builder
    {
        return CostEntry::query()->where(function (Builder $query): void {
            $query
                ->where(fn (Builder $q) => $q->where('costable_type', $this->getMorphClass())->where('costable_id', $this->getKey()))
                ->orWhereIn('project_id', $this->projects()->select('projects.id'))
                ->orWhere(fn (Builder $q) => $q->where('costable_type', (new Asset)->getMorphClass())->whereIn('costable_id', $this->assets()->select('assets.id')))
                ->orWhere(fn (Builder $q) => $q->where('costable_type', (new Ticket)->getMorphClass())->whereIn('costable_id', $this->tickets()->select('tickets.id')))
                ->orWhere(fn (Builder $q) => $q->where('costable_type', (new MaintenanceOrder)->getMorphClass())->whereIn('costable_id', $this->maintenanceOrders()->select('maintenance_orders.id')));
        });
    }

    /**
     * @return array<string, float>
     */
    public function costTotals(): array
    {
        return once(function (): array {
            $actual = fn (Builder $query): float => (float) $query->where('kind', CostEntryKind::Actual->value)->sum('amount');

            $totals = [
                'property' => $actual($this->costEntries()->getQuery()),
                'projects' => $actual(CostEntry::query()->whereIn('project_id', $this->projects()->select('projects.id'))),
                'assets' => $actual(CostEntry::query()->where('costable_type', (new Asset)->getMorphClass())->whereIn('costable_id', $this->assets()->select('assets.id'))),
                'tickets' => $actual(CostEntry::query()->where('costable_type', (new Ticket)->getMorphClass())->whereIn('costable_id', $this->tickets()->select('tickets.id'))),
                'maintenance' => $actual(CostEntry::query()->where('costable_type', (new MaintenanceOrder)->getMorphClass())->whereIn('costable_id', $this->maintenanceOrders()->select('maintenance_orders.id'))),
            ];

            $totals['total'] = array_sum($totals);

            return $totals;
        });
    }
}
