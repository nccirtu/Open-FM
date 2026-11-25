<?php

namespace App\Filament\Integrations\FormBuilder;

use Cafm\FormBuilder\Models\Form;
use Cafm\FormBuilder\Policies\FormPolicy as BaseFormPolicy;
use Illuminate\Contracts\Auth\Authenticatable;

class FormPolicy extends BaseFormPolicy
{
    public function replicate(Authenticatable $user, Form $form): bool
    {
        return $this->create($user);
    }

    public function reorder(Authenticatable $user): bool
    {
        return $this->deleteAny($user);
    }
}
