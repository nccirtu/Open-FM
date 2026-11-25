<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesThroughParent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class StructureNodePolicy
{
    use AuthorizesThroughParent;
    use HandlesAuthorization;

    protected function parentOf(Model $record): ?Model
    {
        return $record->property;
    }

    protected function viewAnyAbility(): string
    {
        return 'view_any_property';
    }

    protected function writeAbility(): string
    {
        return 'update_property';
    }
}
