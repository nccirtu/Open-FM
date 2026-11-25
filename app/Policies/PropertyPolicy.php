<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;
use App\Policies\Concerns\AuthorizesManagedRecords;
use Illuminate\Auth\Access\HandlesAuthorization;

class PropertyPolicy
{
    use AuthorizesManagedRecords;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_property');
    }

    public function view(User $user, Property $property): bool
    {
        return $this->allowsManaged($user, 'view', $property);
    }

    public function create(User $user): bool
    {
        return $user->can('create_property');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->allowsManaged($user, 'update', $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->allowsManaged($user, 'delete', $property);
    }

    public function assignManager(User $user, ?Property $property = null): bool
    {
        return $property?->exists
            ? $user->can('update_all_property')
            : $user->can('create_all_property');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_property');
    }

    public function forceDelete(User $user, Property $property): bool
    {
        return $user->can('{{ ForceDelete }}');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('{{ ForceDeleteAny }}');
    }

    public function restore(User $user, Property $property): bool
    {
        return $user->can('{{ Restore }}');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('{{ RestoreAny }}');
    }

    public function replicate(User $user, Property $property): bool
    {
        return $user->can('{{ Replicate }}');
    }

    public function reorder(User $user): bool
    {
        return $user->can('{{ Reorder }}');
    }
}
