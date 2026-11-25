<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

trait HasManager
{
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function isManagedBy(?User $user): bool
    {
        return $user !== null
            && filled($this->getAttribute('manager_id'))
            && (string) $this->getAttribute('manager_id') === (string) $user->getKey();
    }

    public static function permissionSubject(): string
    {
        return Str::snake(class_basename(static::class));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeManagedBy(Builder $query, User|string|null $user): Builder
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        return $query->where($this->qualifyColumn('manager_id'), $userId);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if ($user === null || $user->can('view_all_'.static::permissionSubject())) {
            return $query;
        }

        return $query->managedBy($user);
    }
}
