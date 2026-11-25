<?php

namespace App\Filament\Waste\Resources\WasteContainers\Pages;

use App\Filament\Waste\Resources\WasteContainers\WasteContainerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWasteContainer extends CreateRecord
{
    protected static string $resource = WasteContainerResource::class;

    protected function afterFill(): void
    {
        $this->data['property_id'] = request()->query('property', $this->data['property_id'] ?? null);
        $this->data['structure_node_id'] = request()->query('node', $this->data['structure_node_id'] ?? null);
    }
}
