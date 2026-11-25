<?php

namespace App\Filament\Energy\Resources\MeterResource\Pages;

use App\Filament\Energy\Resources\MeterResource;
use App\Services\Energy\ConsumptionAggregator;
use Filament\Resources\Pages\CreateRecord;

class CreateMeter extends CreateRecord
{
    protected static string $resource = MeterResource::class;

    protected function afterCreate(): void
    {
        if ($this->getRecord()->isVirtual()) {
            app(ConsumptionAggregator::class)->refresh($this->getRecord());
        }
    }
}
