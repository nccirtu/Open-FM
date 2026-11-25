<?php

namespace App\Filament\Imports\Concerns;

use App\Models\Organization;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Facades\Filament;

trait ImportsForOrganization
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): void
    {
        $organization = $this->organization();

        if ($organization === null) {
            throw new RowImportFailedException(__('assets.import.errors.no_organization'));
        }

        $previousTenant = Filament::getTenant();
        $previousTeamId = getPermissionsTeamId();

        Filament::setTenant($organization, isQuiet: true);
        setPermissionsTeamId($organization->getKey());

        try {
            $this->beforeRow();

            parent::__invoke($data);
        } finally {
            Filament::setTenant($previousTenant, isQuiet: true);
            setPermissionsTeamId($previousTeamId);
        }
    }

    protected function beforeRow(): void {}

    protected function organizationId(): string
    {
        return (string) $this->organization()?->getKey();
    }

    protected function organization(): ?Organization
    {
        $organizationId = $this->options['organization_id'] ?? Filament::getTenant()?->getKey();

        return $organizationId === null ? null : once(fn (): ?Organization => Organization::query()->find($organizationId));
    }
}
