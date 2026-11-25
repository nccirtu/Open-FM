<?php

namespace App\Models;

use App\Enums\WasteCollectionSource;
use App\Enums\WasteMassSource;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteCollectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteCollection extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteCollectionFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_collections';

    protected $fillable = [
        'waste_pickup_id',
        'waste_container_id',
        'property_id',
        'waste_fraction_id',
        'company_id',
        'project_id',
        'asset_id',
        'collected_at',
        'mass_kg',
        'mass_source',
        'fill_level_pct',
        'volume_l',
        'waste_treatment_id',
        'source',
        'receipt',
        'document_number',
        'certificate_number',
        'volume_m3',
        'batteries_removed',
        'note',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'collected_at' => 'datetime',
            'mass_kg' => 'decimal:2',
            'mass_source' => WasteMassSource::class,
            'fill_level_pct' => 'integer',
            'volume_l' => 'integer',
            'volume_m3' => 'decimal:2',
            'batteries_removed' => 'boolean',
            'source' => WasteCollectionSource::class,
        ];
    }

    public function pickup(): BelongsTo
    {
        return $this->belongsTo(WastePickup::class, 'waste_pickup_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(WasteContainer::class, 'waste_container_id');
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'waste_fraction_id');
    }

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(WasteTreatment::class, 'waste_treatment_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
