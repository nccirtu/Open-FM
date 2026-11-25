<?php

namespace App\Filament\Waste\Resources\WasteSchedules\Pages;

use App\Filament\Waste\Resources\WasteSchedules\WasteScheduleResource;
use App\Models\WasteSchedule;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\Width;

class ManageWasteSchedules extends ManageRecords
{
    protected static string $resource = WasteScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->modalWidth(Width::ThreeExtraLarge)
                ->mutateDataUsing(fn (array $data): array => WasteScheduleResource::prepareData($data))
                ->after(fn (WasteSchedule $record) => WasteScheduleResource::generate($record)),
        ];
    }
}
