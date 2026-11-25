<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PluginSource: string implements HasLabel
{
    case Marketplace = 'marketplace';
    case Upload = 'upload';
    case Composer = 'composer';

    public function getLabel(): string
    {
        return __("plugins.source.{$this->value}");
    }
}
