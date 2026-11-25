<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\WasteContainerTypeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WasteContainerType extends Model
{
    use BelongsToOrganization;

    /** @use HasFactory<WasteContainerTypeFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'waste_container_types';

    protected $fillable = [
        'key',
        'name',
        'default_volume_l',
        'sort',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_volume_l' => 'integer',
            'sort' => 'integer',
        ];
    }

    public function containers(): HasMany
    {
        return $this->hasMany(WasteContainer::class);
    }
}
