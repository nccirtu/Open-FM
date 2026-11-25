<?php

use App\Http\Middleware\ScopeApiRequestsToTokenOrganization;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware(['auth:sanctum', ScopeApiRequestsToTokenOrganization::class, 'throttle:api'])
    ->name('api.v1.')
    ->group(function (): void {
        foreach (glob(__DIR__.'/api/v1/*.php') as $routeFile) {
            require $routeFile;
        }
    });
