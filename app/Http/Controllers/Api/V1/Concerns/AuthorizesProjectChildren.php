<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait AuthorizesProjectChildren
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function whereProjectVisible(Builder $query): Builder
    {
        return $query->whereHas('project', fn (Builder $project) => $project->visibleTo($this->apiUser()));
    }

    protected function authorizeProjectWrite(?string $projectId): void
    {
        if ($projectId !== null) {
            $this->authorizeApi('update', Project::query()->findOrFail($projectId));
        }
    }
}
