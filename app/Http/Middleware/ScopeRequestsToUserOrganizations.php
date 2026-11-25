<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeRequestsToUserOrganizations
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null, 403);

        $organizationIds = $user->organizations()->pluck('organizations.id')->all();

        abort_if($organizationIds === [], 403, 'Der Benutzer ist keinem Mandanten zugeordnet.');

        setPermissionsTeamId($organizationIds[0]);

        ApplyTenantScopes::scopeToOrganizations($organizationIds);

        return $next($request);
    }
}
