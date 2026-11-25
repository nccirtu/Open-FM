<?php

namespace App\Models;

use App\Enums\MaintenanceIntervalUnit;
use App\Enums\MaintenanceType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MaintenancePlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class MaintenancePlan extends Model
{
    /** @use HasFactory<MaintenancePlanFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;
    use InteractsWithMediaFiles;

    protected $fillable = [
        'asset_id',
        'name',
        'type',
        'legal_basis',
        'interval_value',
        'interval_unit',
        'next_due_at',
        'lead_time_days',
        'company_id',
        'inspection_company_id',
        'contract_id',
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
            'next_due_at' => 'date',
            'is_active' => 'boolean',
            'tasks' => 'array',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function inspectionCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'inspection_company_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(MaintenanceOrder::class);
    }

    public function advanceNextDueDate(): void
    {
        $this->next_due_at = $this->interval_unit->addTo($this->next_due_at, $this->interval_value);
        $this->save();
    }
}
