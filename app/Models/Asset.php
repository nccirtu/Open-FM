<?php

namespace App\Models;

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Flow\Concerns\HasWorkflows;
use App\Models\Concerns\BelongsToOrganization;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class Asset extends Model
{
    use BelongsToOrganization;
    use HasFactory;
    use HasUuids;
    use HasWorkflows;
    use InteractsWithMediaFiles;

    /**
     * @var string
     */
    protected $table = 'assets';

    /**
     * @var array
     */
    protected $guarded = ['organization_id', 'asset_number'];

    protected $casts = [
        'last_maintenance_at' => 'date',
        'next_maintenance_at' => 'date',
        'condition' => AssetCondition::class,
        'service_status' => AssetServiceStatus::class,
        'warranty_until' => 'date',
        'installed_at' => 'date',
        'decommissioned_at' => 'date',
        'purchase_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Asset $asset): void {
            if (filled($asset->asset_number)) {
                return;
            }

            $organizationId = $asset->organization_id ?? Filament::getTenant()?->getKey();

            if ($organizationId === null) {
                return;
            }

            $asset->asset_number = ((int) static::query()->withoutGlobalScopes()->where('organization_id', $organizationId)->max('asset_number')) + 1;
        });
    }

    public function warrantyColor(): string
    {
        if ($this->warranty_until === null) {
            return 'gray';
        }

        return match (true) {
            $this->warranty_until->isPast() => 'danger',
            $this->warranty_until->lessThanOrEqualTo(now()->addDays(90)) => 'warning',
            default => 'success',
        };
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function structureNode(): BelongsTo
    {
        return $this->belongsTo(StructureNode::class);
    }

    public function parentAsset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'parent_asset_id');
    }

    public function childAssets(): HasMany
    {
        return $this->hasMany(Asset::class, 'parent_asset_id');
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inspectionCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'inspection_company_id');
    }

    public function files(): MorphToMany
    {
        return $this->mediaFiles('files');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function maintenancePlans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }

    public function maintenanceOrders(): HasMany
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function measuringMeters(): BelongsToMany
    {
        return $this->belongsToMany(Meter::class)->withPivot('share');
    }

    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class);
    }

    public function costEntries(): MorphMany
    {
        return $this->morphMany(CostEntry::class, 'costable');
    }
}
