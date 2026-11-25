<?php

namespace App\Policies;

use App\Models\ProjectTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_project::template');
    }

    public function view(User $user, ProjectTemplate $projectTemplate): bool
    {
        return $user->can('view_project::template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_project::template');
    }

    public function update(User $user, ProjectTemplate $projectTemplate): bool
    {
        return $user->can('update_project::template');
    }

    public function delete(User $user, ProjectTemplate $projectTemplate): bool
    {
        return $user->can('delete_project::template');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_project::template');
    }

    public function replicate(User $user, ProjectTemplate $projectTemplate): bool
    {
        return $user->can('create_project::template');
    }
}
