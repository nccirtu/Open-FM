<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Support\ServicePartner;

trait AuthorizesMediaAccess
{
    private const MEDIA_VIEW_ABILITIES = [
        'view_any_property', 'view_any_project', 'view_any_contract', 'view_any_company',
    ];

    private const MEDIA_WRITE_ABILITIES = [
        'update_property', 'update_project', 'update_contract', 'update_company',
    ];

    protected function canViewMedia(User $user): bool
    {
        return $user->canAny(self::MEDIA_VIEW_ABILITIES) && ! ServicePartner::isExternal($user);
    }

    protected function canWriteMedia(User $user): bool
    {
        return $user->canAny(self::MEDIA_WRITE_ABILITIES) && ! ServicePartner::isExternal($user);
    }
}
