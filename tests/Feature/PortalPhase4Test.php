<?php

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Filament\Portal\Resources\AssetResource\Pages\ListAssets;
use App\Filament\Portal\Resources\TicketResource;
use App\Filament\Portal\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Portal\Resources\TicketResource\Pages\ViewTicket;
use App\Models\Asset;
use App\Models\Property;
use App\Models\Role;
use App\Models\StructureNode;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function loginAsMelderPhase4(?Property $assignedProperty = null): User
{
    test()->seed(RolesAndPermissionsSeeder::class);

    $organization = defaultOrganization();

    setPermissionsTeamId($organization->getKey());

    $melder = User::factory()->create(['email' => 'melder-phase4@example.com']);
    $melder->organizations()->syncWithoutDetaching($organization);
    $melder->assignRole(Role::query()->where('name', 'Melder')->where('organization_id', $organization->getKey())->firstOrFail());

    if ($assignedProperty !== null) {
        $melder->properties()->attach($assignedProperty);
    }

    test()->actingAs($melder);

    Filament::setTenant($organization);
    Filament::setCurrentPanel('portal');
    Filament::getPanel('portal')->boot();

    return $melder;
}

it('limits reportable properties to the assigned properties', function () {
    $zugeordnet = null;

    test()->seed(RolesAndPermissionsSeeder::class);

    inOrganization(defaultOrganization(), function () use (&$zugeordnet): void {
        $zugeordnet = Property::factory()->create();
        Property::factory()->create();
    });

    loginAsMelderPhase4($zugeordnet);

    expect(TicketResource::reportableProperties()->pluck('id')->all())->toBe([$zugeordnet->getKey()]);
});

it('offers all tenant properties when no properties are assigned', function () {
    test()->seed(RolesAndPermissionsSeeder::class);

    inOrganization(defaultOrganization(), function (): void {
        Property::factory()->count(2)->create();
    });

    loginAsMelderPhase4();

    expect(TicketResource::reportableProperties()->count())->toBe(2);
});

it('creates a portal report with structure reference', function () {
    $property = null;
    $room = null;

    test()->seed(RolesAndPermissionsSeeder::class);

    inOrganization(defaultOrganization(), function () use (&$property, &$room): void {
        $property = Property::factory()->create();
        $room = StructureNode::factory()->create(['property_id' => $property->getKey(), 'name' => 'Lager']);
    });

    $melder = loginAsMelderPhase4($property);

    Livewire::test(CreateTicket::class)
        ->fillForm([
            'property_id' => $property->getKey(),
            'structure_node_id' => $room->getKey(),
            'title' => 'Wasserschaden im Lager',
            'category' => TicketCategory::Plumbing->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $ticket = Ticket::query()->firstOrFail();

    expect($ticket->structure_node_id)->toBe($room->getKey());
    expect($ticket->reported_by)->toBe($melder->getKey());
});

it('shows only assets of assigned properties in the portal', function () {
    $property = null;
    $eigenesAsset = null;
    $fremdesAsset = null;

    test()->seed(RolesAndPermissionsSeeder::class);

    inOrganization(defaultOrganization(), function () use (&$property, &$eigenesAsset, &$fremdesAsset): void {
        $property = Property::factory()->create();
        $eigenesAsset = Asset::factory()->create(['property_id' => $property->getKey()]);
        $fremdesAsset = Asset::factory()->create();
    });

    loginAsMelderPhase4($property);

    Livewire::test(ListAssets::class)
        ->assertCanSeeTableRecords([$eigenesAsset])
        ->assertCanNotSeeTableRecords([$fremdesAsset]);
});

it('confirms a resolved ticket with rating and closes it', function () {
    $melder = loginAsMelderPhase4();

    $ticket = Ticket::factory()->create();
    $ticket->forceFill(['reported_by' => $melder->getKey(), 'status' => TicketStatus::Resolved])->saveQuietly();

    Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction(TestAction::make('confirmResolution'), data: ['rating' => 5])
        ->assertHasNoActionErrors();

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Closed);
    expect($ticket->rating)->toBe(5);
});

it('reopens a resolved ticket and records the reason as public reply', function () {
    $melder = loginAsMelderPhase4();

    $ticket = Ticket::factory()->create();
    $ticket->forceFill(['reported_by' => $melder->getKey(), 'status' => TicketStatus::Resolved, 'resolved_at' => now()])->saveQuietly();

    Livewire::test(ViewTicket::class, ['record' => $ticket->getRouteKey()])
        ->callAction(TestAction::make('reopen'), data: ['reason' => 'Es tropft immer noch.'])
        ->assertHasNoActionErrors();

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::Reported);
    expect($ticket->resolved_at)->toBeNull();
    expect($ticket->replies()->count())->toBe(1);
    expect($ticket->replies()->first()->message)->toContain('Es tropft immer noch.');
    expect($ticket->replies()->first()->is_internal_note)->toBeFalsy();
});

it('notifies the reporter when the status changes', function () {
    $melder = loginAsMelderPhase4();

    $ticket = Ticket::factory()->create();
    expect($ticket->reported_by)->toBe($melder->getKey());

    $admin = User::factory()->create(['email' => 'bearbeiter@example.com']);
    $admin->organizations()->syncWithoutDetaching(defaultOrganization());
    $admin->assignRole('Admin');
    test()->actingAs($admin);

    $ticket->update(['status' => TicketStatus::InProgress]);

    $notified = DB::table('notifications')
        ->where('notifiable_id', $melder->getKey())
        ->count();

    expect($notified)->toBeGreaterThan(0);
});

it('prefills asset reports from the portal asset list link', function () {
    $property = null;
    $asset = null;

    test()->seed(RolesAndPermissionsSeeder::class);

    inOrganization(defaultOrganization(), function () use (&$property, &$asset): void {
        $property = Property::factory()->create();
        $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    });

    loginAsMelderPhase4($property);

    $url = TicketResource::getUrl('create', ['property_id' => $property->getKey(), 'asset_id' => $asset->getKey()]);

    expect($url)->toContain('property_id='.$property->getKey());
    expect($url)->toContain('asset_id='.$asset->getKey());
});
