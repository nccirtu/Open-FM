<?php

namespace App\Filament\Admin\Resources\WorkflowResource\Pages;

use App\Filament\Admin\Resources\WorkflowResource;
use Filament\Actions\Action;
use Packstub\Flow\Filament\Resources\WorkflowResource\Pages\EditWorkflow as BaseEditWorkflow;

class EditWorkflow extends BaseEditWorkflow
{
    protected static string $resource = WorkflowResource::class;

    public function settingsAction(): Action
    {
        return parent::settingsAction()
            ->schema(WorkflowResource::withTenantSelect(WorkflowResource::detailsSchema()))
            ->mutateDataUsing(fn (array $data): array => WorkflowResource::withTenantType($data));
    }
}
