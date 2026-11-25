<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Slimani\MediaManager\Models\Folder;

class EntityMediaFolder extends Model
{
    use HasUuids;

    protected $fillable = ['folder_id', 'folderable_type', 'folderable_id'];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function folderable(): MorphTo
    {
        return $this->morphTo();
    }
}
