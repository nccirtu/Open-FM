<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesThroughParent;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Database\Eloquent\Model;

class CompanyContactPolicy
{
    use AuthorizesThroughParent;
    use HandlesAuthorization;

    protected function parentOf(Model $record): ?Model
    {
        return $record->company;
    }

    protected function viewAnyAbility(): string
    {
        return 'view_any_company';
    }

    protected function writeAbility(): string
    {
        return 'update_company';
    }
}
