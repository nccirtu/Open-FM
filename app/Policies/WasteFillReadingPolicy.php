<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteFillReading;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteFillReadingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::container');
    }

    public function view(User $user, WasteFillReading $wasteFillReading): bool
    {
        return $user->can('view_waste::container');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WasteFillReading $wasteFillReading): bool
    {
        return false;
    }

    public function delete(User $user, WasteFillReading $wasteFillReading): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
