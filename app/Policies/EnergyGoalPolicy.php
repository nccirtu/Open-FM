<?php

namespace App\Policies;

use App\Models\EnergyGoal;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EnergyGoalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_energy::goal');
    }

    public function view(User $user, EnergyGoal $energyGoal): bool
    {
        return $user->can('view_energy::goal');
    }

    public function create(User $user): bool
    {
        return $user->can('create_energy::goal');
    }

    public function update(User $user, EnergyGoal $energyGoal): bool
    {
        return $user->can('update_energy::goal');
    }

    public function delete(User $user, EnergyGoal $energyGoal): bool
    {
        return $user->can('delete_energy::goal');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_energy::goal');
    }
}
