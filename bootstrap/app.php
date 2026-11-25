<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\CheckOrganizationEmailDomain;
use App\Http\Middleware\ScopeApiRequestsToTokenOrganization;
use App\Http\Middleware\ScopeRequestsToUserOrganizations;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = (string) env('TRUSTED_PROXIES', '');

        $middleware->trustProxies(at: $trustedProxies === '*'
            ? '*'
            : array_values(array_filter(array_map('trim', explode(',', $trustedProxies)))));

        $middleware->append(AddSecurityHeaders::class);

        $middleware->web(append: [
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->group('filament', [
            'web',
            CheckOrganizationEmailDomain::class,
        ]);

        $middleware->prependToPriorityList(SubstituteBindings::class, ScopeApiRequestsToTokenOrganization::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, ScopeRequestsToUserOrganizations::class);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
