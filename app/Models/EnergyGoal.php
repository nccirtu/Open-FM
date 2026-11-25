<?php

namespace App\Models;

use App\Enums\EnergyGoalPeriod;
use App\Enums\EnergyGoalType;
use App\Enums\EnergyMedium;
use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\EnergyGoalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class EnergyGoal extends Model
{
    /** @use HasFactory<EnergyGoalFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    protected $table = 'energy_goals';

    protected $fillable = [
        'name',
        'goalable_type',
        'goalable_id',
        'medium',
        'type',
        'period',
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
            'medium' => EnergyMedium::class,
            'type' => EnergyGoalType::class,
            'period' => EnergyGoalPeriod::class,
            'year' => 'integer',
            'target_value' => 'decimal:2',
            'baseline_year' => 'integer',
        ];
    }

    public function goalable(): MorphTo
    {
        return $this->morphTo();
    }
}
