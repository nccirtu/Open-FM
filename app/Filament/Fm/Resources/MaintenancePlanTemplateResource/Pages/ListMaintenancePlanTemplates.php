<?php

namespace App\Filament\Fm\Resources\MaintenancePlanTemplateResource\Pages;

use App\Filament\Fm\Resources\MaintenancePlanTemplateResource;
use App\Services\MaintenanceCatalogImporter;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMaintenancePlanTemplates extends ListRecords
{
    protected static string $resource = MaintenancePlanTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->importCatalogAction(),
            CreateAction::make(),
        ];
    }

    protected function importCatalogAction(): Action
    {
        return Action::make('importCatalog')
            ->label(__('maintenance.catalogs.import_label'))
            ->icon('heroicon-o-arrow-down-tray')
            ->visible(fn (): bool => auth()->user()?->can('create_maintenance::plan::template') ?? false)
            ->modalHeading(__('maintenance.catalogs.import_label'))
            ->modalSubmitActionLabel(__('maintenance.catalogs.import_submit'))
            ->schema([
                Select::make('country')
                    ->label(__('maintenance.templates.country'))
                    ->options(MaintenanceCatalogImporter::countryOptions())
                    ->required()
                    ->native(false),
            ])
            ->action(function (array $data): void {
                $imported = app(MaintenanceCatalogImporter::class)
                    ->import(Filament::getTenant(), $data['country']);

                Notification::make()
                    ->title(__('maintenance.catalogs.imported', ['count' => $imported]))
                    ->success()
                    ->send();
            });
    }
}
