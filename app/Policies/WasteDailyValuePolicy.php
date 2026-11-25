<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteDailyValue;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteDailyValuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::collection');
    }

    public function view(User $user, WasteDailyValue $wasteDailyValue): bool
    {
        return $user->can('view_waste::collection');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WasteDailyValue $wasteDailyValue): bool
    {
        return false;
    }

    public function delete(User $user, WasteDailyValue $wasteDailyValue): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
