<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

trait EnforcesManagerAssignment
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! static::getResource()::canAssignManager()) {
            $data['manager_id'] = Filament::auth()->id();
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! static::getResource()::canAssignManager($this->getRecord())) {
            unset($data['manager_id']);
        }

        return $data;
    }
}
