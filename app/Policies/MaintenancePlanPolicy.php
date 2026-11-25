<?php

namespace App\Policies;

use App\Models\MaintenancePlan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_maintenance::plan');
    }

    public function view(User $user, MaintenancePlan $plan): bool
    {
        return $user->can('view_maintenance::plan');
    }

    public function create(User $user): bool
    {
        return $user->can('create_maintenance::plan');
    }

    public function update(User $user, MaintenancePlan $plan): bool
    {
        return $user->can('update_maintenance::plan');
    }

    public function delete(User $user, MaintenancePlan $plan): bool
    {
        return $user->can('delete_maintenance::plan');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_maintenance::plan');
    }
}
