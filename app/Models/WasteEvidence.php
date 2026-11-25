<?php

namespace App\Models;

use App\Enums\WasteEvidenceStatus;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<int, string>|null $attachments
 */
class WasteEvidence extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteEvidenceFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_evidence';

    protected $fillable = [
        'property_id',
        'company_id',
        'waste_fraction_id',
        'year',
        'type',
        'status',
        'valid_until',
        'attachments',
        'note',
    ];

    protected $attributes = [
        'status' => 'open',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'status' => WasteEvidenceStatus::class,
            'valid_until' => 'date',
            'attachments' => 'array',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fraction(): BelongsTo
    {
        return $this->belongsTo(WasteFraction::class, 'waste_fraction_id');
    }

    public function typeLabel(): string
    {
        return self::labelFor($this->type);
    }

    public static function labelFor(string $type): string
    {
        $key = "waste.evidence_types.{$type}";

        return trans()->has($key) ? __($key) : $type;
    }
}
