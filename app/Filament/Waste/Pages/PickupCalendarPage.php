<?php

namespace App\Filament\Waste\Pages;

use App\Filament\Waste\Widgets\PickupCalendarWidget;
use App\Models\WastePickup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PickupCalendarPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'abfuhrkalender';

    protected string $view = 'filament.waste.pages.pickup-calendar';

    public static function getNavigationGroup(): ?string
    {
        return __('waste.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('waste.pages.calendar');
    }

    public function getTitle(): string
    {
        return __('waste.pages.calendar');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('viewAny', WastePickup::class) ?? false;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PickupCalendarWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
