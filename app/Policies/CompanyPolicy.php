<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CompanyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->restrictedToCompanyId() === null && $user->can('view_any_company');
    }

    public function view(User $user, Company $company): bool
    {
        $restrictedTo = $user->restrictedToCompanyId();

        if ($restrictedTo !== null) {
            return $restrictedTo === $company->getKey();
        }

        return $user->can('view_company');
    }

    public function create(User $user): bool
    {
        return $user->restrictedToCompanyId() === null && $user->can('create_company');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->restrictedToCompanyId() === null && $user->can('update_company');
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->restrictedToCompanyId() === null && $user->can('delete_company');
    }

    public function deleteAny(User $user): bool
    {
        return $user->restrictedToCompanyId() === null && $user->can('delete_any_company');
    }
}
