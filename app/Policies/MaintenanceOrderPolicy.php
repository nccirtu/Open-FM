<?php

namespace App\Policies;

use App\Models\MaintenanceOrder;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenanceOrderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_maintenance::order');
    }

    public function view(User $user, MaintenanceOrder $order): bool
    {
        return $user->can('view_maintenance::order');
    }

    public function create(User $user): bool
    {
        return $user->can('create_maintenance::order');
    }

    public function update(User $user, MaintenanceOrder $order): bool
    {
        return $user->can('update_maintenance::order');
    }

    public function delete(User $user, MaintenanceOrder $order): bool
    {
        return $user->can('delete_maintenance::order');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_maintenance::order');
    }
}
