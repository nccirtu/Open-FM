<?php

namespace App\Policies;

use App\Models\MaintenancePlanTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MaintenancePlanTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_maintenance::plan::template');
    }

    public function view(User $user, MaintenancePlanTemplate $template): bool
    {
        return $user->can('view_maintenance::plan::template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_maintenance::plan::template');
    }

    public function update(User $user, MaintenancePlanTemplate $template): bool
    {
        return $user->can('update_maintenance::plan::template');
    }

    public function delete(User $user, MaintenancePlanTemplate $template): bool
    {
        return $user->can('delete_maintenance::plan::template');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_maintenance::plan::template');
    }
}
