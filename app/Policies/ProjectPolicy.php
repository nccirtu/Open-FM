<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagedRecords;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use AuthorizesManagedRecords;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_project');
    }

    public function view(User $user, Project $project): bool
    {
        return $this->allowsManaged($user, 'view', $project);
    }

    public function create(User $user): bool
    {
        return $user->can('create_project');
    }

    public function update(User $user, Project $project): bool
    {
        return $this->allowsManaged($user, 'update', $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->allowsManaged($user, 'delete', $project);
    }

    public function assignManager(User $user, ?Project $project = null): bool
    {
        return $project?->exists
            ? $user->can('update_all_project')
            : $user->can('create_all_project');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_project');
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    public function restore(User $user, Project $project): bool
    {
        return $user->can('{{ Restore }}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    public function replicate(User $user, Project $project): bool
    {
        return $user->can('{{ Replicate }}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
