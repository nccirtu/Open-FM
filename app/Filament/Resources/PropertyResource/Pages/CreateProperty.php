<?php

namespace App\Filament\Resources\PropertyResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Concerns\EnforcesManagerAssignment;
use App\Filament\Resources\PropertyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProperty extends CreateRecord
{
    protected static string $resource = PropertyResource::class;

    use AdoptsMediaFilesIntoFolder;
    use EnforcesManagerAssignment;
}
