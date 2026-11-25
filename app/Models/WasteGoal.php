<?php

namespace App\Models;

use App\Enums\WasteGoalType;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteGoalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteGoal extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteGoalFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_goals';

    protected $fillable = [
        'property_id',
        'name',
        'type',
        'year',
        'target_value',
        'baseline_year',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WasteGoalType::class,
            'year' => 'integer',
            'target_value' => 'decimal:2',
            'baseline_year' => 'integer',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
