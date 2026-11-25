<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Organization;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;

#[Group('Mandant')]
class OrganizationController extends ApiController
{
    protected static string $ability = 'organization';

    #[Endpoint('Mandant anzeigen', 'Der Mandant, dem der API-Token zugeordnet ist.')]
    public function show(): OrganizationResource
    {
        $organization = Organization::query()->findOrFail($this->organizationId());

        $this->authorizeApi('view', $organization);

        return new OrganizationResource($organization);
    }
}
