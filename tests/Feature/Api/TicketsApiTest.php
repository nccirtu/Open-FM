<?php

use App\Models\Asset;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Organization;
use App\Models\Property;
use App\Models\SlaDefinition;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists tickets with filters, sorting and includes', function () {
    $property = Property::factory()->create(['property_number' => '3000']);
    $asset = Asset::factory()->create(['property_id' => $property->getKey()]);
    $agent = User::factory()->create();
    $agent->organizations()->attach(Filament::getTenant());

    $treffer = Ticket::factory()->create([
        'title' => 'Aufzug steht',
        'description' => 'Kabine hängt im 2. OG',
        'property_id' => $property->getKey(),
        'asset_id' => $asset->getKey(),
        'priority' => 'p1',
        'status' => 'in_progress',
        'category' => 'building',
        'assigned_to' => $agent->getKey(),
        'due_at' => '2026-01-10 08:00:00',
    ]);
    Ticket::factory()->create(['title' => 'Birne defekt', 'priority' => 'p3', 'status' => 'reported', 'category' => 'electrical', 'due_at' => '2026-03-01 08:00:00']);

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure(['data' => [['id', 'title', 'status', 'priority', 'type', 'category', 'reported_by', 'links' => ['self']]], 'links', 'meta']);

    $this->getJson('/api/v1/tickets?filter[search]=kabine')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/tickets?filter[status]=in_progress')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[status]=in_progress,reported')->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tickets?filter[priority]=p1')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[category]=electrical')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[type]=request')->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/tickets?filter[property_id]='.$property->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[asset_id]='.$asset->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[assigned_to]='.$agent->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets?filter[due_before]=2026-02-01')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $treffer->getKey());
    $this->getJson('/api/v1/tickets?filter[created_from]='.now()->toDateString())->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/tickets?filter[created_to]='.now()->subDay()->toDateString())->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/tickets?sort=title')->assertJsonPath('data.0.title', 'Aufzug steht');
    $this->getJson('/api/v1/tickets?sort=-priority')->assertJsonPath('data.0.priority', 'p3');

    $this->getJson('/api/v1/tickets?include=property,asset,assignee&filter[priority]=p1')
        ->assertOk()
        ->assertJsonPath('data.0.property.property_number', '3000')
        ->assertJsonPath('data.0.asset.id', $asset->getKey())
        ->assertJsonPath('data.0.assignee.id', $agent->getKey());
});

it('shows a single ticket with replies and reporter', function () {
    $reporter = User::factory()->create(['name' => 'Melder Max']);
    $ticket = Ticket::factory()->create();
    $ticket->forceFill(['reported_by' => $reporter->getKey()])->saveQuietly();
    TicketReply::factory()->create(['ticket_id' => $ticket->getKey(), 'message' => 'Wir kümmern uns']);

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'?include=reporter,replies')
        ->assertOk()
        ->assertJsonPath('data.id', $ticket->getKey())
        ->assertJsonPath('data.reporter.name', 'Melder Max')
        ->assertJsonCount(1, 'data.replies')
        ->assertJsonPath('data.replies.0.message', 'Wir kümmern uns');

    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'?include=photos')->assertStatus(400);
});

it('hides tickets of other organizations', function () {
    $ticket = Ticket::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): Ticket => Ticket::factory()->create());

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $ticket->getKey());
    $this->getJson('/api/v1/tickets/'.$fremd->getKey())->assertNotFound();
    $this->getJson('/api/v1/tickets/'.$fremd->getKey().'/replies')->assertNotFound();
});

it('creates tickets with the token user as reporter and applies the SLA', function () {
    SlaDefinition::factory()->create(['priority' => 'p1', 'response_hours' => 2, 'resolution_hours' => 8]);
    $property = Property::factory()->create();
    $company = Company::factory()->create();
    $contract = Contract::factory()->create();
    $fremdesObjekt = inOrganization(Organization::factory()->create(), fn (): Property => Property::factory()->create());
    $fremderBenutzer = User::factory()->create();

    $user = actingAsApiUser(['tickets:write']);

    $this->postJson('/api/v1/tickets', ['title' => 'Test', 'category' => 'other', 'property_id' => $fremdesObjekt->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['property_id']);

    $this->postJson('/api/v1/tickets', ['title' => 'Test', 'category' => 'other', 'property_id' => $property->getKey(), 'assigned_to' => $fremderBenutzer->getKey()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['assigned_to']);

    $this->postJson('/api/v1/tickets', ['title' => 'Test', 'property_id' => $property->getKey(), 'category' => 'unbekannt', 'priority' => 'p9'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category', 'priority']);

    $response = $this->postJson('/api/v1/tickets', [
        'title' => 'Wasserschaden',
        'description' => 'Decke tropft',
        'property_id' => $property->getKey(),
        'category' => 'plumbing',
        'priority' => 'p1',
        'company_id' => $company->getKey(),
        'contract_id' => $contract->getKey(),
        'due_at' => '2026-10-01T10:00:00+02:00',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Wasserschaden')
        ->assertJsonPath('data.status', 'reported')
        ->assertJsonPath('data.priority', 'p1')
        ->assertJsonPath('data.reported_by', $user->getKey())
        ->assertJsonPath('data.property.id', $property->getKey());

    $ticket = Ticket::query()->findOrFail($response->json('data.id'));

    expect($ticket->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($ticket->respond_due_at)->not->toBeNull()
        ->and((int) $ticket->created_at->diffInHours($ticket->resolve_due_at))->toBe(8);
});

it('updates and deletes tickets', function () {
    $ticket = Ticket::factory()->create(['status' => 'accepted']);

    actingAsApiUser(['tickets:write']);

    $this->patchJson('/api/v1/tickets/'.$ticket->getKey(), ['status' => 'erledigt'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->patchJson('/api/v1/tickets/'.$ticket->getKey(), ['status' => 'resolved', 'resolution_notes' => 'Ventil getauscht', 'rating' => 5])
        ->assertOk()
        ->assertJsonPath('data.status', 'resolved')
        ->assertJsonPath('data.resolution_notes', 'Ventil getauscht')
        ->assertJsonPath('data.rating', 5);

    expect($ticket->refresh()->resolved_at)->not->toBeNull();

    $fremd = inOrganization(Organization::factory()->create(), fn (): Ticket => Ticket::factory()->create());

    $this->patchJson('/api/v1/tickets/'.$fremd->getKey(), ['title' => 'x'])->assertNotFound();
    $this->deleteJson('/api/v1/tickets/'.$fremd->getKey())->assertNotFound();

    $this->deleteJson('/api/v1/tickets/'.$ticket->getKey())->assertNoContent();

    expect(Ticket::query()->find($ticket->getKey()))->toBeNull();
});

it('refuses writes with a read-only ability', function () {
    $ticket = Ticket::factory()->create();

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets/'.$ticket->getKey())->assertOk();
    $this->postJson('/api/v1/tickets', ['title' => 'x', 'category' => 'other', 'property_id' => $ticket->property_id])->assertForbidden();
    $this->patchJson('/api/v1/tickets/'.$ticket->getKey(), ['title' => 'x'])->assertForbidden();
    $this->deleteJson('/api/v1/tickets/'.$ticket->getKey())->assertForbidden();

    actingAsApiUser(['maintenance:write']);

    $this->getJson('/api/v1/tickets')->assertForbidden();
});
