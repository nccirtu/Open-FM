<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ServicePartner
{
    public static function isExternal(?User $user): bool
    {
        return $user !== null
            && $user->hasRoleInAnyOrganization('Dienstleister')
            && ! $user->hasRoleInAnyOrganization(['Admin', 'FM']);
    }

    public static function scopeId(?User $user): ?string
    {
        if (! self::isExternal($user)) {
            return null;
        }

        return $user->restrictedToCompanyId() ?? '';
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function restrict(Builder $query, ?User $user, string $column = 'company_id'): Builder
    {
        if (! self::isExternal($user)) {
            return $query;
        }

        return $query->where($query->qualifyColumn($column), self::scopeId($user));
    }
}
