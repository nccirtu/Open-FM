<?php

namespace App\Filament;

use Filament\Facades\Filament;
use Filament\Navigation\MenuItem;

class PanelSwitcherMenuItems
{
    /**
     * @var array<string, string>
     */
    private const PANEL_ICONS = [
        'admin' => 'heroicon-o-cog-6-tooth',
        'fm' => 'heroicon-o-building-office-2',
        'contracts' => 'heroicon-o-document-text',
        'energy' => 'heroicon-o-bolt',
        'waste' => 'openfm-recycle',
        'pm' => 'heroicon-o-clipboard-document-list',
        'portal' => 'heroicon-o-home',
    ];

    /**
     * @return list<MenuItem>
     */
    public static function make(): array
    {
        return collect(self::PANEL_ICONS)
            ->map(fn (string $icon, string $panelId): MenuItem => MenuItem::make()
                ->label(fn (): string => __("app.panels.{$panelId}"))
                ->icon($icon)
                ->url(function () use ($panelId): string {
                    $panel = Filament::getPanel($panelId);

                    return $panel->getUrl($panel->hasTenancy() ? Filament::getTenant() : null) ?? '/';
                })
                ->visible(fn (): bool => Filament::getCurrentPanel()?->getId() !== $panelId
                    && (auth()->user()?->canAccessPanel(Filament::getPanel($panelId)) ?? false)))
            ->values()
            ->all();
    }
}
