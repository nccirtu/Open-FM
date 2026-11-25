<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale
            ?? Filament::getTenant()?->default_locale;

        if (filled($locale) && array_key_exists($locale, Organization::LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
