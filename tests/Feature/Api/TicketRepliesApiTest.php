<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Filament\Facades\Filament;

beforeEach(function (): void {
    Filament::setTenant(Organization::factory()->create(), isQuiet: true);
});

it('lists the replies of a ticket with filters and sorting', function () {
    $author = User::factory()->create(['name' => 'Anna Agent']);
    $ticket = Ticket::factory()->create();
    $public = TicketReply::factory()->create(['ticket_id' => $ticket->getKey(), 'message' => 'Öffentlich', 'created_at' => now()->subHour()]);
    $public->forceFill(['user_id' => $author->getKey()])->saveQuietly();
    $internal = TicketReply::factory()->create(['ticket_id' => $ticket->getKey(), 'message' => 'Intern', 'is_internal_note' => true]);
    TicketReply::factory()->create();

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'/replies')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $public->getKey())
        ->assertJsonPath('data.0.user.name', 'Anna Agent')
        ->assertJsonPath('data.0.ticket_id', $ticket->getKey())
        ->assertJsonStructure(['data' => [['id', 'ticket_id', 'user', 'message', 'is_internal_note', 'created_at', 'links' => ['self']]], 'meta']);

    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'/replies?filter[is_internal_note]=true')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $internal->getKey());
    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'/replies?filter[is_internal_note]=false')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $public->getKey());
    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'/replies?filter[user_id]='.$author->getKey())->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/tickets/'.$ticket->getKey().'/replies?sort=-created_at')->assertJsonPath('data.0.id', $internal->getKey());

    $this->getJson('/api/v1/replies/'.$public->getKey())->assertOk()->assertJsonPath('data.message', 'Öffentlich');
});

it('hides replies of other organizations', function () {
    $reply = TicketReply::factory()->create();
    $fremd = inOrganization(Organization::factory()->create(), fn (): TicketReply => TicketReply::factory()->create());

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/tickets/'.$reply->ticket_id.'/replies')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $reply->getKey());
    $this->getJson('/api/v1/replies/'.$reply->getKey())->assertOk();
    $this->getJson('/api/v1/replies/'.$fremd->getKey())->assertNotFound();
    $this->getJson('/api/v1/tickets/'.$fremd->ticket_id.'/replies')->assertNotFound();
});

it('creates replies as the token user and stops the SLA response clock', function () {
    $ticket = Ticket::factory()->create();

    $user = actingAsApiUser(['tickets:write']);

    $this->postJson('/api/v1/tickets/'.$ticket->getKey().'/replies', ['message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['message']);

    $response = $this->postJson('/api/v1/tickets/'.$ticket->getKey().'/replies', ['message' => 'Techniker unterwegs', 'is_internal_note' => true])
        ->assertCreated()
        ->assertJsonPath('data.message', 'Techniker unterwegs')
        ->assertJsonPath('data.is_internal_note', true)
        ->assertJsonPath('data.user.id', $user->getKey())
        ->assertJsonPath('data.ticket_id', $ticket->getKey());

    $reply = TicketReply::query()->findOrFail($response->json('data.id'));

    expect($reply->organization_id)->toBe(Filament::getTenant()->getKey())
        ->and($ticket->refresh()->first_response_at)->not->toBeNull();

    $fremd = inOrganization(Organization::factory()->create(), fn (): Ticket => Ticket::factory()->create());

    $this->postJson('/api/v1/tickets/'.$fremd->getKey().'/replies', ['message' => 'x'])->assertNotFound();
});

it('never updates or deletes replies, not even for admins', function () {
    $reply = TicketReply::factory()->create(['message' => 'Alt']);

    actingAsApiUser(['tickets:write']);

    $this->patchJson('/api/v1/replies/'.$reply->getKey(), ['message' => 'Neu'])->assertForbidden();
    $this->deleteJson('/api/v1/replies/'.$reply->getKey())->assertForbidden();

    $fremd = inOrganization(Organization::factory()->create(), fn (): TicketReply => TicketReply::factory()->create());

    $this->patchJson('/api/v1/replies/'.$fremd->getKey(), ['message' => 'x'])->assertNotFound();
    $this->deleteJson('/api/v1/replies/'.$fremd->getKey())->assertNotFound();

    expect($reply->fresh()->message)->toBe('Alt');
});

it('refuses writes with a read-only ability', function () {
    $reply = TicketReply::factory()->create();

    actingAsApiUser(['tickets:read']);

    $this->getJson('/api/v1/replies/'.$reply->getKey())->assertOk();
    $this->postJson('/api/v1/tickets/'.$reply->ticket_id.'/replies', ['message' => 'x'])->assertForbidden();
    $this->patchJson('/api/v1/replies/'.$reply->getKey(), ['message' => 'x'])->assertForbidden();
    $this->deleteJson('/api/v1/replies/'.$reply->getKey())->assertForbidden();
});
