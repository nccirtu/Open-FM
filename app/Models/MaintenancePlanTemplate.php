<?php

namespace App\Models;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MaintenancePlanTemplateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenancePlanTemplate extends Model
{
    /** @use HasFactory<MaintenancePlanTemplateFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    protected $fillable = [
        'asset_type_id',
        'country',
        'name',
        'type',
        'legal_basis',
        'interval_value',
        'interval_unit',
        'lead_time_days',
        'is_active',
        'notes',
        'tasks',
    ];

    protected $attributes = [
        'type' => 'maintenance',
        'interval_unit' => 'months',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MaintenanceType::class,
            'interval_unit' => MaintenanceIntervalUnit::class,
            'is_active' => 'boolean',
            'tasks' => 'array',
        ];
    }

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(MaintenancePlan::class);
    }
}
