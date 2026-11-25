<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Database\Factories\BimModelFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BimModel extends Model
{
    /** @use HasFactory<BimModelFactory> */
    use BelongsToOrganization, HasFactory;

    use HasUuids;

    protected $fillable = [
        'property_id',
        'name',
        'file_path',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
