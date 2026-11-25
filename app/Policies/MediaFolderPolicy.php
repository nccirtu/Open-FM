<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesMediaAccess;
use Illuminate\Auth\Access\HandlesAuthorization;
use Slimani\MediaManager\Models\Folder as MediaFolder;

class MediaFolderPolicy
{
    use AuthorizesMediaAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewMedia($user);
    }

    public function view(User $user, MediaFolder $folder): bool
    {
        return $this->canViewMedia($user);
    }

    public function create(User $user): bool
    {
        return $this->canWriteMedia($user);
    }

    public function update(User $user, MediaFolder $folder): bool
    {
        return $this->canWriteMedia($user);
    }

    public function delete(User $user, MediaFolder $folder): bool
    {
        return $this->canWriteMedia($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canWriteMedia($user);
    }
}
