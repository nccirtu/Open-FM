<?php

namespace App\Policies\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesThroughParent
{
    abstract protected function parentOf(Model $record): ?Model;

    abstract protected function viewAnyAbility(): string;

    abstract protected function writeAbility(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->viewAnyAbility());
    }

    public function view(User $user, Model $record): bool
    {
        $parent = $this->parentOf($record);

        return $parent !== null && $user->can('view', $parent);
    }

    public function create(User $user): bool
    {
        return $user->can($this->writeAbility());
    }

    public function update(User $user, Model $record): bool
    {
        $parent = $this->parentOf($record);

        return $parent !== null && $user->can('update', $parent);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can($this->writeAbility());
    }

    public function reorder(User $user): bool
    {
        return $user->can($this->writeAbility());
    }
}
