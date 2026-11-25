<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteTreatmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WasteTreatment extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteTreatmentFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_treatments';

    protected $fillable = [
        'waste_treatment_category_id',
        'code',
        'name',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WasteTreatmentCategory::class, 'waste_treatment_category_id');
    }

    public function label(): string
    {
        return filled($this->code) ? "{$this->code} · {$this->name}" : $this->name;
    }
}
