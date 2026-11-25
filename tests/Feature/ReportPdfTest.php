<?php

use App\Enums\MaintenanceIntervalUnit;
use App\Http\Controllers\MaintenanceCalendarPdfController;
use App\Models\Asset;
use App\Models\Contract;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use App\Models\StructureNode;
use App\Models\Ticket;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View;

it('downloads the property dossier with all sections', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $floor = StructureNode::factory()->floor()->create(['property_id' => $property->getKey()]);
    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey()]);
    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    MaintenancePlan::factory()->create(['asset_id' => $asset->getKey()]);
    Ticket::factory()->create(['property_id' => $property->getKey()]);

    $contract = Contract::factory()->create();
    $contract->properties()->attach($property);

    $response = $this->get(route('property.dossier.pdf', $property));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('objektakte-');
});

it('lists the structure tree in the dossier room book', function () {
    loginAsAdmin();

    $property = Property::factory()->create();
    $floor = StructureNode::factory()->floor()->create(['property_id' => $property->getKey(), 'name' => 'EG']);
    StructureNode::factory()->create(['property_id' => $property->getKey(), 'parent_id' => $floor->getKey(), 'name' => 'Lager']);

    $structureRows = null;
    View::composer('pdfs.objektakte', function (ViewContract $view) use (&$structureRows): void {
        $structureRows = $view->getData()['structureRows'];
    });

    $this->get(route('property.dossier.pdf', $property))->assertOk();

    expect(collect($structureRows)->map(fn (array $row): array => [$row['node']->name, $row['depth']])->all())
        ->toBe([['EG', 0], ['Lager', 1]]);
});

it('returns 404 for dossiers of foreign organizations', function () {
    loginAsAdmin();

    $fremdeOrganisation = Organization::factory()->create();
    $fremderProperty = inOrganization($fremdeOrganisation, fn (): Property => Property::factory()->create());

    $this->get(route('property.dossier.pdf', $fremderProperty))->assertNotFound();
});

it('downloads the yearly maintenance calendar', function () {
    loginAsAdmin();

    MaintenancePlan::factory()->create([
        'next_due_at' => now()->addMonth()->toDateString(),
        'interval_value' => 3,
    ]);

    $response = $this->get(route('maintenance.calendar.pdf', ['year' => now()->year]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('wartungskalender-'.now()->year);
});

it('projects due months of a plan into the requested year', function () {
    loginAsAdmin();

    $plan = MaintenancePlan::factory()->create([
        'next_due_at' => now()->setDate(now()->year, 2, 15)->toDateString(),
        'interval_value' => 3,
        'interval_unit' => MaintenanceIntervalUnit::Months,
    ]);

    $controller = new MaintenanceCalendarPdfController;
    $method = new ReflectionMethod($controller, 'dueMonths');

    expect($method->invoke($controller, $plan, now()->year))->toBe([2, 5, 8, 11]);
    expect($method->invoke($controller, $plan, now()->year + 1))->toBe([2, 5, 8, 11]);
});
