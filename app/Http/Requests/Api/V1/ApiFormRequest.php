<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * @param  Model|class-string<Model>|null  $subject
     */
    protected function allows(string $ability, Model|string|null $subject): bool
    {
        return $subject !== null && Gate::forUser($this->user())->allows($ability, $subject);
    }

    protected function allowsThroughParent(string $ability, ?Model $record, string $parentRelation): bool
    {
        abort_if($record?->getRelationValue($parentRelation) === null, 404);

        return $this->allows($ability, $record);
    }
}
