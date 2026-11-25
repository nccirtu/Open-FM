<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassificationNode extends Model
{
    use BelongsToOrganization;
    use HasUuids;

    protected $fillable = ['organization_id', 'parent_id', 'code', 'name', 'sort'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function label(): string
    {
        return $this->code.' — '.$this->name;
    }

    public function root(): self
    {
        return $this->parent === null ? $this : $this->parent->root();
    }

    public function ancestorGroup(): self
    {
        $node = $this;

        while ($node->parent !== null && $node->parent->parent_id !== null) {
            $node = $node->parent;
        }

        return $node;
    }

    /**
     * @return array<int, string>
     */
    public function selfAndDescendantIds(): array
    {
        return array_merge(
            [$this->getKey()],
            $this->children->flatMap(fn (self $child): array => $child->selfAndDescendantIds())->all(),
        );
    }
}
