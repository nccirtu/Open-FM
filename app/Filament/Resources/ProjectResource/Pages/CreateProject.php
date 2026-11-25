<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\EnforcesManagerAssignment;
use App\Filament\Resources\ProjectResource;
use App\Models\ProjectTemplate;
use App\Services\EntityFolders;
use App\Services\ProjectTemplateApplier;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use EnforcesManagerAssignment;

    protected static string $resource = ProjectResource::class;

    protected function afterCreate(): void
    {
        EntityFolders::adoptRootFiles($this->getRecord());

        $templateId = $this->form->getRawState()['template_id'] ?? null;

        if (blank($templateId)) {
            return;
        }

        $template = ProjectTemplate::query()->find($templateId);

        if ($template !== null) {
            app(ProjectTemplateApplier::class)->apply($template, $this->getRecord());
        }
    }
}
