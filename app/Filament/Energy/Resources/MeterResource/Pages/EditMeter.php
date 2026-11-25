<?php

namespace App\Filament\Energy\Resources\MeterResource\Pages;

use App\Filament\Energy\Resources\MeterResource;
use App\Services\Energy\ConsumptionAggregator;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMeter extends EditRecord
{
    protected static string $resource = MeterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        app(ConsumptionAggregator::class)->refresh($this->getRecord());
    }
}
