<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PluginStatus: string implements HasColor, HasLabel
{
    case Installed = 'installed';
    case Installing = 'installing';
    case Updating = 'updating';
    case Removing = 'removing';
    case Failed = 'failed';

    public function getLabel(): string
    {
        return __("plugins.status.{$this->value}");
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Installed => 'success',
            self::Installing, self::Updating, self::Removing => 'info',
            self::Failed => 'danger',
        };
    }

    public function isBusy(): bool
    {
        return in_array($this, [self::Installing, self::Updating, self::Removing], true);
    }
}
