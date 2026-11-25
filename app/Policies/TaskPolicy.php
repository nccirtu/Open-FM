<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_task');
    }

    public function view(User $user, Task $task): bool
    {
        if (! $user->can('view_task')) {
            return false;
        }

        return (string) $task->assignee_id === (string) $user->getKey()
            || ($task->project !== null && $user->can('view', $task->project));
    }

    public function create(User $user): bool
    {
        return $user->can('create_task') && $user->can('update_project');
    }

    public function update(User $user, Task $task): bool
    {
        return $user->can('update_task') && $task->project !== null && $user->can('update', $task->project);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('delete_task') && $task->project !== null && $user->can('update', $task->project);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_task');
    }

    public function reorder(User $user): bool
    {
        return $user->can('update_task');
    }
}
