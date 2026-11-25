<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Spatie\QueryBuilder\QueryBuilder;

abstract class ApiController extends Controller implements HasMiddleware
{
    protected static string $ability = '';

    public static function middleware(): array
    {
        if (static::$ability === '') {
            return [];
        }

        return [
            new Middleware('ability:'.static::$ability.':read,'.static::$ability.':write', only: ['index', 'show']),
            new Middleware('ability:'.static::$ability.':write', except: ['index', 'show']),
        ];
    }

    protected function apiUser(): User
    {
        $user = request()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    /**
     * @param  Model|class-string<Model>  $subject
     */
    protected function authorizeApi(string $ability, Model|string $subject): void
    {
        Gate::forUser($this->apiUser())->authorize($ability, $subject);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  Model|class-string<Model>  $subject
     * @return array<string, mixed>
     */
    protected function enforceManagerAssignment(array $data, Model|string $subject): array
    {
        if (Gate::forUser($this->apiUser())->allows('assignManager', $subject)) {
            return $data;
        }

        if ($subject instanceof Model) {
            unset($data['manager_id']);

            return $data;
        }

        $data['manager_id'] = $this->apiUser()->getKey();

        return $data;
    }

    protected function paginate(QueryBuilder|Builder $query, Request $request): LengthAwarePaginator
    {
        $perPage = min(
            max($request->integer('per_page', (int) config('api.default_per_page', 25)), 1),
            (int) config('api.max_per_page', 100),
        );

        return $query->paginate($perPage)->appends($request->query());
    }

    /**
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    protected function requestedIncludes(Request $request, array $allowed): array
    {
        $requested = array_filter(array_map('trim', explode(',', (string) $request->query('include', ''))));

        $unknown = array_diff($requested, $allowed);

        abort_if($unknown !== [], 400, __('api.errors.unknown_include', ['includes' => implode(', ', $unknown)]));

        return array_values($requested);
    }

    protected function organizationId(): ?string
    {
        return ApiContext::organizationId();
    }
}
