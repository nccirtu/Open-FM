<?php

namespace App\Policies;

use App\Models\ClassificationNode;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClassificationNodePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->canAny(['view_any_asset', 'view_any_project']);
    }

    public function view(User $user, ClassificationNode $classificationNode): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, ClassificationNode $classificationNode): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, ClassificationNode $classificationNode): bool
    {
        return $this->manage($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->manage($user);
    }

    public function import(User $user): bool
    {
        return $this->manage($user);
    }

    private function manage(User $user): bool
    {
        return $user->canAny(['update_asset', 'update_project']);
    }
}
