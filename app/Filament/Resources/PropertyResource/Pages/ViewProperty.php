<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Concerns\CombinesDetailsWithRelationTabs;
use App\Filament\Resources\PropertyResource;
use App\Models\Property;
use App\Support\Plugins\QrCodeBridge;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    use CombinesDetailsWithRelationTabs;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make(),
                Action::make('bim')
                    ->label('BIM')
                    ->icon('heroicon-o-cube-transparent')
                    ->url(fn (): string => PropertyResource::getUrl('bim', ['record' => $this->getRecord()])),
                Action::make('structure')
                    ->label(__('property.structure.models'))
                    ->icon('heroicon-o-building-office-2')
                    ->url(fn (): string => PropertyResource::getUrl('structure', ['record' => $this->getRecord()])),
                Action::make('dossier')
                    ->label(__('reports.dossier.action'))
                    ->icon('heroicon-o-document-text')
                    ->url(fn (): string => route('property.dossier.pdf', $this->getRecord()))
                    ->openUrlInNewTab(),
                QrCodeBridge::action(),
                QrCodeBridge::printLabelsAction(
                    'printStructureLabels',
                    records: fn (Property $record) => $record->structureNodes()->orderBy('sort')->orderBy('name')->get(),
                    fileName: fn (Property $record): string => $record->property_number.'-'.__('property.structure.models'),
                    label: __('property.qr.print_structure'),
                ),
                QrCodeBridge::printLabelsAction(
                    'printAssetLabels',
                    records: fn (Property $record) => $record->assets()->with(['property', 'structureNode'])->orderBy('name')->get(),
                    fileName: fn (Property $record): string => $record->property_number.'-assets',
                    label: __('property.qr.print_assets'),
                ),
                DeleteAction::make(),
            ]),
        ];
    }
}
