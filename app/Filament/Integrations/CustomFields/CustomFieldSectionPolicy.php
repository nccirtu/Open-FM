<?php

namespace App\Filament\Integrations\CustomFields;

use Cafm\CustomFields\Models\CustomFieldSection;
use Cafm\CustomFields\Policies\CustomFieldSectionPolicy as BaseCustomFieldSectionPolicy;
use Illuminate\Contracts\Auth\Authenticatable;

class CustomFieldSectionPolicy extends BaseCustomFieldSectionPolicy
{
    public function replicate(Authenticatable $user, CustomFieldSection $section): bool
    {
        return $this->create($user);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $this->deleteAny($user);
    }
}
