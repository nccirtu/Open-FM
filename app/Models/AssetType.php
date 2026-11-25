<?php

namespace App\Models;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetType extends Model
{
    use HasUuids;

    protected $fillable = ['name'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function classifications(): HasMany
    {
        return $this->hasMany(AssetTypeClassification::class);
    }

    public function categoryForCurrentTenant(): ?ClassificationNode
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return null;
        }

        return $this->classifications()
            ->where('organization_id', $tenant->getKey())
            ->first()
            ?->classificationNode;
    }

    public function displayCodeForCurrentTenant(): ?string
    {
        $tenant = Filament::getTenant();

        if ($tenant === null) {
            return null;
        }

        $classification = $this->classifications()->where('organization_id', $tenant->getKey())->first();

        if ($classification === null) {
            return null;
        }

        $sequence = AssetTypeClassification::query()
            ->where('organization_id', $classification->organization_id)
            ->where('classification_node_id', $classification->classification_node_id)
            ->where('id', '<=', $classification->getKey())
            ->count();

        return $classification->classificationNode->code.'-'.$sequence;
    }

    public function displayLabelForCurrentTenant(): string
    {
        $code = $this->displayCodeForCurrentTenant();

        return $code === null ? $this->name : "{$code} {$this->name}";
    }
}
