<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Gate;

trait ReadOnlyWithoutOwnerUpdate
{
    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }
}
