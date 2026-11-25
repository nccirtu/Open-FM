<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WasteEvidence;
use Illuminate\Auth\Access\HandlesAuthorization;

class WasteEvidencePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_waste::evidence');
    }

    public function view(User $user, WasteEvidence $wasteEvidence): bool
    {
        return $user->can('view_waste::evidence');
    }

    public function create(User $user): bool
    {
        return $user->can('create_waste::evidence');
    }

    public function update(User $user, WasteEvidence $wasteEvidence): bool
    {
        return $user->can('update_waste::evidence');
    }

    public function delete(User $user, WasteEvidence $wasteEvidence): bool
    {
        return $user->can('delete_waste::evidence');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_waste::evidence');
    }
}
