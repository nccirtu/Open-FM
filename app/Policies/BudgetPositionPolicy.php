<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesThroughParent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class BudgetPositionPolicy
{
    use AuthorizesThroughParent;
    use HandlesAuthorization;

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
