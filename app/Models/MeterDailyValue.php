<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, float>|null $intervals
 */
class MeterDailyValue extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $table = 'meter_daily_values';

    protected $fillable = [
        'meter_id',
        'date',
        'consumption',
        'intervals',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'consumption' => 'decimal:4',
            'intervals' => 'array',
        ];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }
}
