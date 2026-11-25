<?php

namespace App\Filament\Admin\Resources\WorkflowResource\Pages;

use App\Filament\Admin\Resources\WorkflowResource;
use Packstub\Flow\Filament\Resources\WorkflowResource\Pages\CreateWorkflow as BaseCreateWorkflow;

class CreateWorkflow extends BaseCreateWorkflow
{
    protected static string $resource = WorkflowResource::class;

    protected function detailsSchema(): array
    {
        return WorkflowResource::withTenantSelect(parent::detailsSchema());
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return WorkflowResource::withTenantType(parent::mutateFormDataBeforeCreate($data));
    }
}
