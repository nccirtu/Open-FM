<?php

namespace App\Policies;

use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeterReadingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_meter::reading');
    }

    public function view(User $user, MeterReading $meterReading): bool
    {
        return $user->can('view_meter::reading');
    }

    public function create(User $user): bool
    {
        return $user->can('create_meter::reading');
    }

    public function update(User $user, MeterReading $meterReading): bool
    {
        return $user->can('update_meter::reading');
    }

    public function delete(User $user, MeterReading $meterReading): bool
    {
        return $user->can('delete_meter::reading');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_meter::reading');
    }
}
