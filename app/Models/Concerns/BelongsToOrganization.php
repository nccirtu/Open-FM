<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::creating(function (Model $model): void {
            if (blank($model->getAttribute('organization_id'))) {
                $model->setAttribute('organization_id', Filament::getTenant()?->getKey());
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
