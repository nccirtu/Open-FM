<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

#[Group('Benutzer')]
class UserController extends ApiController
{
    protected static string $ability = 'users';

    #[Endpoint('Benutzer auflisten', 'Paginierte Liste der Benutzer, die dem Mandanten des Tokens angehören.')]
    #[QueryParameter('filter[search]', description: 'Volltext über Name und E-Mail.', type: 'string')]
    #[QueryParameter('sort', description: 'Sortierfeld, Minus für absteigend: name, created_at.', type: 'string', default: 'name')]
    #[QueryParameter('per_page', description: 'Einträge je Seite (1–100).', type: 'integer', default: 25)]
    #[QueryParameter('page', description: 'Seite.', type: 'integer', default: 1)]
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorizeApi('viewAny', User::class);

        $users = QueryBuilder::for($this->tenantUsers())
            ->allowedFilters(
                AllowedFilter::callback('search', fn (Builder $query, string $value) => $query->where(fn (Builder $inner) => $inner
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('email', 'like', "%{$value}%"))),
            )
            ->allowedSorts('name', 'created_at')
            ->defaultSort('name');

        return UserResource::collection($this->paginate($users, $request));
    }

    #[Endpoint('Benutzer anzeigen')]
    public function show(User $user): UserResource
    {
        $this->authorizeApi('view', $user);

        abort_unless($this->tenantUsers()->whereKey($user->getKey())->exists(), 404);

        return new UserResource($user);
    }

    /**
     * @return Builder<User>
     */
    private function tenantUsers(): Builder
    {
        return User::query()->whereHas('organizations', fn (Builder $query) => $query->whereKey($this->organizationId()));
    }
}
