<?php

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WasteContainer;

return [

    'tenant' => [
        'model' => Organization::class,
        'foreign_key' => 'organization_id',
        'title_attribute' => 'name',
    ],

    'admin_roles' => ['Admin', 'super_admin'],

    'locales' => Organization::LOCALES,

    'entities' => [
        'property' => ['label' => 'Objekt', 'model' => Property::class, 'title' => 'property_number', 'search' => ['property_number', 'city', 'name']],
        'structure_node' => ['label' => 'Strukturelement', 'model' => StructureNode::class, 'title' => 'name', 'search' => ['name']],
        'asset' => ['label' => 'Asset', 'model' => Asset::class, 'title' => 'name', 'search' => ['name', 'manufacturer']],
        'project' => ['label' => 'Projekt', 'model' => Project::class, 'title' => 'name', 'search' => ['name', 'code']],
        'ticket' => ['label' => 'Störung', 'model' => Ticket::class, 'title' => 'title', 'search' => ['title']],
        'contract' => ['label' => 'Vertrag', 'model' => Contract::class, 'title' => 'name', 'search' => ['name']],
        'user' => ['label' => 'Benutzer', 'model' => User::class, 'title' => 'name', 'search' => ['name', 'email']],
        'asset_type' => ['label' => 'Asset-Typ', 'model' => AssetType::class, 'title' => 'name', 'search' => ['name']],
        'company' => ['label' => 'Firma / Lieferant', 'model' => Company::class, 'title' => 'name', 'search' => ['name', 'email']],
        'waste_container' => ['label' => 'Abfallbehälter', 'model' => WasteContainer::class, 'title' => 'number', 'search' => ['number']],
    ],

    'uploads' => [
        'disk' => env('FILAMENT_FILESYSTEM_DISK', 'uploads'),
        'directory' => 'form-builder',
    ],

    'webhook' => [
        'tries' => 3,
        'timeout' => 10,
    ],

];
