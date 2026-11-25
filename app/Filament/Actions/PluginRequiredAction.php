<?php

namespace App\Filament\Actions;

use App\Filament\Admin\Pages\ManagePlugins;
use App\Support\Plugins\Plugins;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;

class PluginRequiredAction
{
    public static function make(string $name, string $plugin): Action
    {
        return static::configure(Action::make($name), $plugin);
    }

    public static function bulk(string $name, string $plugin): BulkAction
    {
        return static::configure(BulkAction::make($name), $plugin);
    }

    /**
     * @template TAction of Action
     */
    public static function configure(Action $action, string $plugin): Action
    {
        $definition = Plugins::definition($plugin);

        return $action
            ->icon($definition->icon)
            ->color('gray')
            ->badge(__('plugins.badge'))
            ->badgeColor('gray')
            ->modalIcon($definition->icon)
            ->modalHeading(__('plugins.required.heading', ['plugin' => $definition->name()]))
            ->modalDescription(fn (): string => Plugins::unavailableReason($plugin).' '.(Plugins::canManage()
                ? __('plugins.required.install_hint')
                : __('plugins.required.contact_admin')))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('plugins.required.close'))
            ->extraModalFooterActions(fn (): array => Plugins::canManage() ? [
                Action::make('managePlugins')
                    ->label(__('plugins.required.manage'))
                    ->icon('heroicon-o-puzzle-piece')
                    ->url(ManagePlugins::getUrl(['plugin' => $plugin], panel: 'admin')),
            ] : [])
            ->action(fn () => null);
    }
}
