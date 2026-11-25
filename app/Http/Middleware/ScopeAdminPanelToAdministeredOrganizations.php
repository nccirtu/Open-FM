<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScopeAdminPanelToAdministeredOrganizations
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationIds = $request->user()?->administeredOrganizationIds();

        if ($organizationIds !== null) {
            ApplyTenantScopes::scopeToOrganizations($organizationIds);
        }

        return $next($request);
    }
}
