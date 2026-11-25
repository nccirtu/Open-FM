<?php

namespace App\Policies;

use App\Models\MeterDailyValue;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeterDailyValuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_meter');
    }

    public function view(User $user, MeterDailyValue $meterDailyValue): bool
    {
        return $user->can('view_meter');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MeterDailyValue $meterDailyValue): bool
    {
        return false;
    }

    public function delete(User $user, MeterDailyValue $meterDailyValue): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
