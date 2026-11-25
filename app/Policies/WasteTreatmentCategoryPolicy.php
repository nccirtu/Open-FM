<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteTreatmentCategory;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteTreatmentCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::treatment::category');
    }

    public function view(User $user, WasteTreatmentCategory $wasteTreatmentCategory): bool
    {
        return $user->can('view_waste::treatment::category');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::treatment::category');
    }

    public function update(User $user, WasteTreatmentCategory $wasteTreatmentCategory): bool
    {
        return $user->can('update_waste::treatment::category');
    }

    public function delete(User $user, WasteTreatmentCategory $wasteTreatmentCategory): bool
    {
        return $user->can('delete_waste::treatment::category');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::treatment::category');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_waste::treatment::category');
    }
}
