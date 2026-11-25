<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTypeClassification extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $fillable = ['organization_id', 'asset_type_id', 'classification_node_id'];

    public function assetType(): BelongsTo
    {
        return $this->belongsTo(AssetType::class);
    }

    public function classificationNode(): BelongsTo
    {
        return $this->belongsTo(ClassificationNode::class);
    }
}
