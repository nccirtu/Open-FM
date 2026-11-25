<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Resources\PropertyResource;
use App\Support\Plugins\QrCodeBridge;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class StructurePage extends Page
{
    use InteractsWithRecord;

    protected static string $resource = PropertyResource::class;

    protected string $view = 'filament.pages.property-structure';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        abort_unless(PropertyResource::canView($this->record), 403);
    }

    protected function getHeaderActions(): array
    {
        return [
            QrCodeBridge::printLabelsAction(
                'printQrLabels',
                records: fn () => $this->record->structureNodes()->orderBy('sort')->orderBy('name')->get(),
                fileName: fn (): string => $this->record->property_number.'-'.__('property.structure.models'),
            ),
        ];
    }

    public function getTitle(): string
    {
        return __('property.structure.models').' — '.$this->record->property_number;
    }
}
