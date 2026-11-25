<?php

use App\Enums\AssetCondition;
use App\Enums\AssetServiceStatus;
use App\Enums\StructureNodeType;
use App\Filament\Resources\AssetResource\Pages\ListAssets;
use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Company;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use Filament\Facades\Filament;
use Livewire\Livewire;

it('filters assets by location including sub-locations', function () {
    loginAsAdmin();
    $property = Property::factory()->create();
    $building = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);
    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $building->getKey(), 'type' => StructureNodeType::Room]);
    $inBuilding = Asset::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $building->getKey()]);
    $inRoom = Asset::factory()->create(['property_id' => $property->getKey(), 'structure_node_id' => $room->getKey()]);
    $elsewhere = Asset::factory()->create();

    Livewire::test(ListAssets::class)
        ->filterTable('location', ['property_id' => $property->getKey()])
        ->assertCanSeeTableRecords([$inBuilding, $inRoom])
        ->assertCanNotSeeTableRecords([$elsewhere])
        ->filterTable('location', ['property_id' => $property->getKey(), 'structure_node_id' => $building->getKey(), 'include_sub_locations' => true])
        ->assertCanSeeTableRecords([$inBuilding, $inRoom])
        ->filterTable('location', ['property_id' => $property->getKey(), 'structure_node_id' => $building->getKey(), 'include_sub_locations' => false])
        ->assertCanSeeTableRecords([$inBuilding])
        ->assertCanNotSeeTableRecords([$inRoom]);
});

it('filters assets by type, condition, status, partner and manufacturer', function () {
    loginAsAdmin();
    $lift = AssetType::query()->firstOrCreate(['name' => 'Aufzug']);
    $partner = Company::factory()->create();
    $match = Asset::factory()->create([
        'asset_type_id' => $lift->getKey(),
        'condition' => AssetCondition::Poor,
        'service_status' => AssetServiceStatus::OutOfOperation,
        'company_id' => $partner->getKey(),
        'inspection_company_id' => $partner->getKey(),
        'manufacturer' => 'Schindler',
    ]);
    $other = Asset::factory()->create(['condition' => AssetCondition::Good, 'manufacturer' => 'Vaillant']);

    $page = Livewire::test(ListAssets::class);

    foreach ([
        ['asset_type_id', [$lift->getKey()]],
        ['condition', [AssetCondition::Poor]],
        ['service_status', [AssetServiceStatus::OutOfOperation]],
        ['company_id', [$partner]],
        ['inspection_company_id', [$partner]],
        ['manufacturer', ['Schindler']],
    ] as [$filter, $data]) {
        $page->resetTableFilters()
            ->filterTable($filter, $data)
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }
});

it('filters assets by maintenance, warranty, installation, hierarchy, decommissioning and BIM link', function () {
    loginAsAdmin();
    $overdue = Asset::factory()->create(['next_maintenance_at' => today()->subDay(), 'warranty_until' => today()->subYear(), 'installed_at' => '2015-05-01']);
    $soon = Asset::factory()->create(['next_maintenance_at' => today()->addDays(10), 'warranty_until' => today()->addDays(30), 'installed_at' => '2022-01-10', 'ifc_guid' => '2O2Fr$t4X7Zf8NOew3FLOH']);
    $child = Asset::factory()->create(['parent_asset_id' => $soon->getKey(), 'property_id' => $soon->property_id, 'decommissioned_at' => today()]);

    Livewire::test(ListAssets::class)
        ->filterTable('maintenance', 'overdue')
        ->assertCanSeeTableRecords([$overdue])->assertCanNotSeeTableRecords([$soon, $child])
        ->filterTable('maintenance', 'due_30')
        ->assertCanSeeTableRecords([$soon])->assertCanNotSeeTableRecords([$overdue, $child])
        ->filterTable('maintenance', 'none')
        ->assertCanSeeTableRecords([$child])->assertCanNotSeeTableRecords([$overdue, $soon])
        ->resetTableFilters()
        ->filterTable('warranty', 'expired')
        ->assertCanSeeTableRecords([$overdue])->assertCanNotSeeTableRecords([$soon])
        ->filterTable('warranty', 'expiring')
        ->assertCanSeeTableRecords([$soon])->assertCanNotSeeTableRecords([$overdue])
        ->resetTableFilters()
        ->filterTable('installed_at', ['installed_from' => '2020-01-01'])
        ->assertCanSeeTableRecords([$soon])->assertCanNotSeeTableRecords([$overdue])
        ->resetTableFilters()
        ->filterTable('hierarchy', false)
        ->assertCanSeeTableRecords([$child])->assertCanNotSeeTableRecords([$overdue, $soon])
        ->resetTableFilters()
        ->filterTable('decommissioned', true)
        ->assertCanSeeTableRecords([$child])->assertCanNotSeeTableRecords([$overdue, $soon])
        ->resetTableFilters()
        ->filterTable('bim', true)
        ->assertCanSeeTableRecords([$soon])->assertCanNotSeeTableRecords([$overdue, $child]);
});

it('offers the extended asset filters in the API', function () {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
    $property = Property::factory()->create();
    $building = StructureNode::factory()->building()->create(['property_id' => $property->getKey()]);
    $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $building->getKey()]);
    $inspector = Company::factory()->create();
    $parent = Asset::factory()->create([
        'property_id' => $property->getKey(),
        'structure_node_id' => $room->getKey(),
        'next_maintenance_at' => today()->subDay(),
        'warranty_until' => today()->subDay(),
        'inspection_company_id' => $inspector->getKey(),
        'serial_number' => 'SN-4711-X',
        'ifc_guid' => '0K8kKqCxL7cOv3sZq0x9aB',
        'installed_at' => '2018-04-01',
    ]);
    $child = Asset::factory()->create([
        'property_id' => $property->getKey(),
        'parent_asset_id' => $parent->getKey(),
        'next_maintenance_at' => today()->addMonth(),
        'decommissioned_at' => today(),
        'installed_at' => '2024-04-01',
    ]);

    actingAsApiUser(['assets:read']);

    $ids = fn (string $query): array => collect($this->getJson('/api/v1/assets?'.$query)->assertOk()->json('data'))->pluck('id')->all();

    expect($ids('filter[location]='.$building->getKey()))->toBe([$parent->getKey()])
        ->and($ids('filter[top_level]=true'))->toBe([$parent->getKey()])
        ->and($ids('filter[top_level]=false'))->toBe([$child->getKey()])
        ->and($ids('filter[parent_asset_id]='.$parent->getKey()))->toBe([$child->getKey()])
        ->and($ids('filter[maintenance_overdue]=true'))->toBe([$parent->getKey()])
        ->and($ids('filter[next_maintenance_after]='.today()->toDateString()))->toBe([$child->getKey()])
        ->and($ids('filter[warranty_expired]=true'))->toBe([$parent->getKey()])
        ->and($ids('filter[installed_after]=2020-01-01'))->toBe([$child->getKey()])
        ->and($ids('filter[installed_before]=2020-01-01'))->toBe([$parent->getKey()])
        ->and($ids('filter[decommissioned]=true'))->toBe([$child->getKey()])
        ->and($ids('filter[bim_linked]=true'))->toBe([$parent->getKey()])
        ->and($ids('filter[inspection_company_id]='.$inspector->getKey()))->toBe([$parent->getKey()])
        ->and($ids('filter[serial_number]=4711'))->toBe([$parent->getKey()]);
});
