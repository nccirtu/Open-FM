<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteSchedule;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteSchedulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::schedule');
    }

    public function view(User $user, WasteSchedule $wasteSchedule): bool
    {
        return $user->can('view_waste::schedule');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::schedule');
    }

    public function update(User $user, WasteSchedule $wasteSchedule): bool
    {
        return $user->can('update_waste::schedule');
    }

    public function delete(User $user, WasteSchedule $wasteSchedule): bool
    {
        return $user->can('delete_waste::schedule');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::schedule');
    }
}
