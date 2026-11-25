<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteSettings;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteSettingsPolicy
{
    use HandlesAuthorization;

    public function update(User $user, ?WasteSettings $wasteSettings = null): bool
    {
        return $user->can('update_waste::fraction');
    }
}
