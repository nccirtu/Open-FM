<?php

namespace App\Policies;

use App\Models\ChangeOrder;
use App\Models\User;
use App\Policies\Concerns\AuthorizesThroughParent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class ChangeOrderPolicy
{
    use AuthorizesThroughParent;
    use HandlesAuthorization;

    public function decide(User $user, ChangeOrder $changeOrder): bool
    {
        return $this->update($user, $changeOrder);
    }

    protected function parentOf(Model $record): ?Model
    {
        return $record->project;
    }

    protected function viewAnyAbility(): string
    {
        return 'view_any_project';
    }

    protected function writeAbility(): string
    {
        return 'update_project';
    }
}
