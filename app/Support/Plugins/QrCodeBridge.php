<?php

namespace App\Support\Plugins;

use App\Filament\Actions\PluginRequiredAction;
use Cafm\QrCode\Filament\Actions\PrintQrLabelsAction;
use Cafm\QrCode\Filament\Actions\PrintQrLabelsBulkAction;
use Cafm\QrCode\Filament\Actions\QrCodeAction;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;

final class QrCodeBridge
{
    public const PLUGIN = 'qr-code';

    public static function action(): Action
    {
        return Plugins::active(self::PLUGIN)
            ? QrCodeAction::make()
            : PluginRequiredAction::make('qrCode', self::PLUGIN)->label(__('plugins.features.qr_code'));
    }

    public static function printLabelsAction(string $name, Closure $records, Closure $fileName, ?string $label = null): Action
    {
        if (! Plugins::active(self::PLUGIN)) {
            return PluginRequiredAction::make($name, self::PLUGIN)->label($label ?? __('plugins.features.qr_labels'));
        }

        $action = PrintQrLabelsAction::make($name)->records($records)->fileName($fileName);

        return $label === null ? $action : $action->label($label);
    }

    public static function printLabelsBulkAction(): BulkAction
    {
        return Plugins::active(self::PLUGIN)
            ? PrintQrLabelsBulkAction::make()
            : PluginRequiredAction::bulk('printQrLabels', self::PLUGIN)->label(__('plugins.features.qr_labels'));
    }
}
