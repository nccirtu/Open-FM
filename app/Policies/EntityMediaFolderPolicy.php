<?php

namespace App\Policies;

use App\Models\EntityMediaFolder;
use App\Models\User;
use App\Policies\Concerns\AuthorizesMediaAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntityMediaFolderPolicy
{
    use AuthorizesMediaAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewMedia($user);
    }

    public function view(User $user, EntityMediaFolder $entityMediaFolder): bool
    {
        return $this->canViewMedia($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EntityMediaFolder $entityMediaFolder): bool
    {
        return false;
    }

    public function delete(User $user, EntityMediaFolder $entityMediaFolder): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
