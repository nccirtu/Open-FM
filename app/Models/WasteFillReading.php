<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteFillReading extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $table = 'waste_fill_readings';

    protected $fillable = [
        'waste_container_id',
        'measured_at',
        'fill_level_pct',
        'temperature_c',
        'sensor_id',
        'pickup_detected',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'measured_at' => 'datetime',
            'fill_level_pct' => 'integer',
            'temperature_c' => 'decimal:1',
            'pickup_detected' => 'boolean',
        ];
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(WasteContainer::class, 'waste_container_id');
    }
}
