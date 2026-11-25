<?php

namespace App\Policies;

use App\Models\Meter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_meter');
    }

    public function view(User $user, Meter $meter): bool
    {
        return $user->can('view_meter');
    }

    public function create(User $user): bool
    {
        return $user->can('create_meter');
    }

    public function update(User $user, Meter $meter): bool
    {
        return $user->can('update_meter');
    }

    public function delete(User $user, Meter $meter): bool
    {
        return $user->can('delete_meter');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_meter');
    }
}
