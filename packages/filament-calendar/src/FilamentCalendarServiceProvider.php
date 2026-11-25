<?php

namespace OpenFm\FilamentCalendar;

use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCalendarServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-calendar';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasViews()
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-calendar', __DIR__.'/../resources/dist/filament-calendar.css')->loadedOnRequest(),
        ], 'open-fm/filament-calendar');
    }
}
