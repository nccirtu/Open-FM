<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteFractionGroupFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteFractionGroup extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteFractionGroupFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_fraction_groups';

    protected $fillable = [
        'key',
        'name',
        'counts_as_separated',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'counts_as_separated' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function fractions(): HasMany
    {
        return $this->hasMany(WasteFraction::class);
    }
}
