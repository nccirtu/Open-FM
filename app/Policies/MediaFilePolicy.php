<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesMediaAccess;
use Illuminate\Auth\Access\HandlesAuthorization;
use Slimani\MediaManager\Models\File as MediaFile;

class MediaFilePolicy
{
    use AuthorizesMediaAccess;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $this->canViewMedia($user);
    }

    public function view(User $user, MediaFile $file): bool
    {
        return $this->canViewMedia($user);
    }

    public function create(User $user): bool
    {
        return $this->canWriteMedia($user);
    }

    public function update(User $user, MediaFile $file): bool
    {
        return $this->canWriteMedia($user);
    }

    public function delete(User $user, MediaFile $file): bool
    {
        return $this->canWriteMedia($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->canWriteMedia($user);
    }
}
