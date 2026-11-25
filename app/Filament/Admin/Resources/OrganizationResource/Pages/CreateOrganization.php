<?php

namespace App\Filament\Admin\Resources\OrganizationResource\Pages;

use App\Filament\Admin\Resources\OrganizationResource;
use App\Services\Classification\ClassificationTemplateImporter;
use App\Services\MaintenanceCatalogImporter;
use App\Services\OrganizationRoleProvisioner;
use App\Services\Waste\WasteLookupDefaults;
use App\Services\Waste\WasteTemplateImporter;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganization extends CreateRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function afterCreate(): void
    {
        app(OrganizationRoleProvisioner::class)->provision($this->record);

        $catalog = $this->form->getRawState()['maintenance_catalog'] ?? null;

        if ($catalog !== null) {
            app(MaintenanceCatalogImporter::class)->import($this->record, $catalog);
        }

        $template = $this->form->getRawState()['classification_template'] ?? null;

        if ($template !== null) {
            app(ClassificationTemplateImporter::class)->importTemplate($this->record, $template);
        }

        app(WasteLookupDefaults::class)->seed($this->record);

        $wasteTemplate = $this->form->getRawState()['waste_template'] ?? null;

        if ($wasteTemplate !== null) {
            app(WasteTemplateImporter::class)->importTemplate($this->record, $wasteTemplate);
        }
    }
}
