<?php

namespace App\Policies;

use App\Models\CostEntry;
use App\Models\User;
use App\Support\ServicePartner;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class CostEntryPolicy
{
    use HandlesAuthorization;

    private const VIEW_ABILITIES = [
        'view_any_project', 'view_any_property', 'view_any_contract', 'view_any_company',
    ];

    private const WRITE_ABILITIES = [
        'update_project', 'update_property', 'update_contract', 'update_company',
    ];

    public function viewAny(User $user): bool
    {
        return ! ServicePartner::isExternal($user) && $user->canAny(self::VIEW_ABILITIES);
    }

    public function view(User $user, CostEntry $costEntry): bool
    {
        $owner = $this->ownerOf($costEntry);

        return $owner !== null && ! ServicePartner::isExternal($user) && $user->can('view', $owner);
    }

    public function create(User $user): bool
    {
        return ! ServicePartner::isExternal($user) && $user->canAny(self::WRITE_ABILITIES);
    }

    public function update(User $user, CostEntry $costEntry): bool
    {
        $owner = $this->ownerOf($costEntry);

        return $owner !== null && ! ServicePartner::isExternal($user) && $user->can('update', $owner);
    }

    public function delete(User $user, CostEntry $costEntry): bool
    {
        return $this->update($user, $costEntry);
    }

    public function deleteAny(User $user): bool
    {
        return $this->create($user);
    }

    private function ownerOf(CostEntry $costEntry): ?Model
    {
        return $costEntry->project ?? $costEntry->costable ?? $costEntry->company;
    }
}
