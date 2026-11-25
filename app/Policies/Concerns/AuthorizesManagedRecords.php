<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesManagedRecords
{
    protected function allowsManaged(User $user, string $ability, Model $record): bool
    {
        $subject = $record::permissionSubject();

        if (! $user->can("{$ability}_{$subject}")) {
            return false;
        }

        return $user->can("{$ability}_all_{$subject}") || $record->isManagedBy($user);
    }
}
