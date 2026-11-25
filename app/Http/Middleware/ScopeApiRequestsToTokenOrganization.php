<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\ApiContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeApiRequestsToTokenOrganization
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $token = $user->currentAccessToken();

        $organizationId = $token instanceof ApiToken ? $token->getAttribute('organization_id') : null;

        abort_if(! is_string($organizationId) || $organizationId === '', 403, __('api.errors.token_without_organization'));

        abort_unless($user->organizations()->whereKey($organizationId)->exists(), 403, __('api.errors.token_organization_membership'));

        setPermissionsTeamId($organizationId);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        ApiContext::set([$organizationId]);
        ApplyTenantScopes::scopeToOrganizations([$organizationId]);

        return $next($request);
    }
}
