<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Integrations\MapWidget\PropertyMapWidget;
use App\Models\Asset;
use App\Models\MaintenanceOrder;
use App\Models\MaintenancePlan;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Ticket;
use Cafm\MapWidget\MapMarker;
use Livewire\Livewire;

beforeEach(fn () => requiresPlugin('map-widget'));

it('builds markers only for properties with coordinates in the current tenant', function () {
    loginAsAdminInFmPanel();

    $mitKoordinaten = Property::factory()->create(['latitude' => 50.9375, 'longitude' => 6.9603, 'city' => 'Köln']);
    Property::factory()->create(['latitude' => null, 'longitude' => null]);

    $fremdeOrganisation = Organization::factory()->create();
    inOrganization($fremdeOrganisation, fn (): Property => Property::factory()->create(['latitude' => 48.1, 'longitude' => 11.5]));

    $widget = new PropertyMapWidget;
    $markers = $widget->getMarkerData();

    expect($markers)->toHaveCount(1);
    expect($markers[0]['lat'])->toBe(50.9375);
    expect($markers[0]['popup'])->toContain('Köln');
    expect($markers[0]['popup'])->toContain('/fm/');
});

it('colors markers by open tickets and overdue maintenance', function () {
    loginAsAdminInFmPanel();

    $gruen = Property::factory()->create(['latitude' => 50.0, 'longitude' => 8.0]);

    $gelb = Property::factory()->create(['latitude' => 51.0, 'longitude' => 9.0]);
    Ticket::factory()->create(['property_id' => $gelb->getKey(), 'priority' => TicketPriority::P3]);

    $rotNotfall = Property::factory()->create(['latitude' => 52.0, 'longitude' => 10.0]);
    Ticket::factory()->create(['property_id' => $rotNotfall->getKey(), 'priority' => TicketPriority::P1]);

    $rotUeberfaellig = Property::factory()->create(['latitude' => 53.0, 'longitude' => 11.0]);
    $asset = Asset::factory()->create(['property_id' => $rotUeberfaellig->getKey()]);
    $plan = MaintenancePlan::factory()->create(['asset_id' => $asset->getKey(), 'next_due_at' => now()->subMonth()->toDateString()]);
    MaintenanceOrder::firstOrCreateForPlanDueDate($plan);

    $widget = new PropertyMapWidget;
    $farben = collect($widget->getMarkerData())->pluck('color');

    expect($farben)->toContain('#16a34a');
    expect($farben)->toContain('#f59e0b');
    expect($farben->filter(fn (string $farbe): bool => $farbe === '#dc2626')->count())->toBe(2);

    $geloest = Ticket::factory()->create(['property_id' => $gruen->getKey(), 'priority' => TicketPriority::P1]);
    $geloest->forceFill(['status' => TicketStatus::Resolved])->saveQuietly();

    expect(collect((new PropertyMapWidget)->getMarkerData())->pluck('color')->filter(fn (string $farbe): bool => $farbe === '#dc2626')->count())
        ->toBe(2);
});

it('escapes marker popup content', function () {
    $marker = MapMarker::make(50.0, 8.0)
        ->label('<script>alert(1)</script>')
        ->lines(['<b>böse</b>'])
        ->toArray();

    expect($marker['popup'])->not->toContain('<script>');
    expect($marker['popup'])->toContain('&lt;script&gt;');
    expect($marker['popup'])->toContain('&lt;b&gt;');
});

it('renders the map widget on the FM dashboard', function () {
    loginAsAdminInFmPanel();

    Property::factory()->create(['latitude' => 50.9375, 'longitude' => 6.9603]);

    Livewire::test(PropertyMapWidget::class)
        ->assertSuccessful()
        ->assertSee('cafm-map-widget', escape: false);
});
