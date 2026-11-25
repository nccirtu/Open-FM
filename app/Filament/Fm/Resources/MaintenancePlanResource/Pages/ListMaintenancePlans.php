<?php

namespace App\Filament\Fm\Resources\MaintenancePlanResource\Pages;

use App\Filament\Fm\Resources\MaintenancePlanResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMaintenancePlans extends ListRecords
{
    protected static string $resource = MaintenancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                CreateAction::make(),
                Action::make('calendarPdf')
                    ->label(__('reports.calendar.action', ['year' => now()->year]))
                    ->icon('heroicon-o-calendar')
                    ->color('gray')
                    ->url(fn (): string => route('maintenance.calendar.pdf', ['year' => now()->year]))
                    ->openUrlInNewTab(),
            ]),
        ];
    }
}
