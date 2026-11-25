<?php

namespace App\Filament\Contracts\Resources\ContractResource\Pages;

use App\Filament\Concerns\AdoptsMediaFilesIntoFolder;
use App\Filament\Contracts\Resources\ContractResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContract extends CreateRecord
{
    protected static string $resource = ContractResource::class;

    use AdoptsMediaFilesIntoFolder;
}
