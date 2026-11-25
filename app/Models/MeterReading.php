<?php

namespace App\Models;

use App\Enums\MeterReadingSource;
use App\Enums\MeterReadingStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\MeterReadingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeterReading extends Model
{
    /** @use HasFactory<MeterReadingFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    protected $table = 'meter_readings';

    protected $fillable = [
        'meter_id',
        'read_at',
        'value',
        'source',
        'status',
        'flag',
        'photo',
        'note',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'value' => 'decimal:4',
            'source' => MeterReadingSource::class,
            'status' => MeterReadingStatus::class,
        ];
    }

    public function meter(): BelongsTo
    {
        return $this->belongsTo(Meter::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
